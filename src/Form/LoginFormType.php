<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Имя пользователя',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'Введите имя пользователя'
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Пароль',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'Введите пароль'
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'Запомнить меня',
                'required' => false,
                'attr' => ['class' => 'form-check-input mb-3'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Войти',
                'attr' => ['class' => 'btn btn-primary mt-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}