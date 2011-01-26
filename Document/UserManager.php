<?php

namespace Bundle\FOS\UserBundle\Document;

use Bundle\FOS\UserBundle\Util\CanonicalizerInterface;
use Bundle\FOS\UserBundle\Model\UserInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Bundle\FOS\UserBundle\Model\UserManager as BaseUserManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Symfony\Component\Validator\Constraint;

class UserManager extends BaseUserManager
{
    protected $dm;
    protected $repository;
    protected $class;

    public function __construct($encoder, $algorithm, CanonicalizerInterface $canonicalizer, DocumentManager $dm, $class)
    {
        parent::__construct($encoder, $algorithm, $canonicalizer);

        $this->dm = $dm;
        $this->repository = $dm->getRepository($class);

        $metadata = $dm->getClassMetadata($class);
        $this->class = $metadata->name;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteUser(UserInterface $user)
    {
        $this->dm->remove($user);
        $this->dm->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritDoc}
     */
    public function findUserBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function findUsers()
    {
        return $this->repository->findAll();
    }

    /**
     * {@inheritDoc}
     */
    public function updateUser(UserInterface $user)
    {
        $this->updatePassword($user);

        $this->dm->persist($user);
        $this->dm->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function validateUnique($value, Constraint $constraint)
    {
        $classMetadata = $this->dm->getClassMetadata($this->class);
        // TODO: ODM seems to be missing handling for multiple properties
        // $fields = array_map('trim', explode(',', $constraint->property));
        $query = $this->getQueryArray($classMetadata, $value, $constraint->property);

        $document = $this->findUserBy($query);
        if (null === $document) {
            return true;
        }

        // check if document in mongodb is the same document as the checked one
        if ($document->isUser($value)) {
            return true;
        }
        // check if returned document is proxy and initialize the minimum identifier if needed
        if ($document instanceof Proxy) {
            $classMetadata->setIdentifierValue($document, $document->__identifier);
        }
        // check if document has the same identifier as the current one
        if ($classMetadata->getIdentifierValue($document) === $classMetadata->getIdentifierValue($value)) {
            return true;
        }

        return false;
    }

    protected function getQueryArray($classMetadata, $value, $fieldName)
    {
        $field = $this->getFieldNameFromPropertyPath($fieldName);
        if (!isset($classMetadata->fieldMappings[$field])) {
            throw new \LogicException("Mapping for '$fieldName' doesn't exist for " . $this->class);
        }

        $mapping = $classMetadata->fieldMappings[$field];
        if (isset($mapping['reference']) && $mapping['reference']) {
            throw new \LogicException('Cannot determine uniqueness of referenced document values');
        }

        $criteria[$field] = $value instanceOf UserInterface ? $classMetadata->getFieldValue($value, $field) : $value;

        return $criteria;
    }

    /**
     * Returns the actual document field value
     *
     * E.g. document.someVal -> document
     *      user.emails      -> user
     *      username         -> username
     *
     * @param string $field
     * @return string
     */
    protected function getFieldNameFromPropertyPath($field)
    {
        $pieces = explode('.', $field);
        return $pieces[0];
    }
}
