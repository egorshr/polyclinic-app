<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterFormType extends AbstractType
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
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите имя пользователя']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Имя пользователя должно содержать минимум {{ limit }} символа',
                        'max' => 30,
                        'maxMessage' => 'Имя пользователя не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Пароль',
                    'attr' => [
                        'class' => 'form-control mb-3',
                        'placeholder' => 'Введите пароль'
                    ],
                ],
                'second_options' => [
                    'label' => 'Повторите пароль',
                    'attr' => [
                        'class' => 'form-control mb-3',
                        'placeholder' => 'Повторите пароль'
                    ],
                ],
                'invalid_message' => 'Пароли должны совпадать.',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите пароль']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен содержать минимум {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Зарегистрироваться',
                'attr' => ['class' => 'btn btn-primary mt-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'registration'],
        ]);
    }
}