<?php

namespace FOS\UserBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\TextField;
use Symfony\Component\Form\TextareaField;

use Symfony\Component\Validator\ValidatorInterface;

class GroupForm extends Form
{
    /**
     * Constructor.
     *
     * @param string $name
     * @param array|object $data
     * @param ValidatorInterface $validator
     * @param array $options
     */
    public function __construct($name, array $options = array())
    {
        $this->addOption('theme');

        parent::__construct($name, $options);
    }

    public function configure()
    {
        $this->add(new TextField('name'));
    }

}
