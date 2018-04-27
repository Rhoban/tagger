<?php

namespace App\Form;

use FOS\UserBundle\Form\Type\ProfileFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use App\Entity\User;

class ProfileType extends ProfileFormType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('patchesCol');
        $builder->add('patchesRow');
    }

    public function getName()
    {
        return 'app_user_profile';
    }
}
