<?php

namespace App\Form;

use FOS\UserBundle\Form\Type\ProfileFormType;
use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Gregwar\CaptchaBundle\Type\CaptchaType;

use App\Entity\User;

class RegistrationType extends RegistrationFormType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('captcha', CaptchaType::class);

    }

    public function getName()
    {
        return 'app_registration_type';
    }
}
