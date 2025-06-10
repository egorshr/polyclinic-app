<?php

namespace App\Form;

use App\Entity\Patient;
use App\Enum\Gender;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PatientProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Имя',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите ваше имя'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите ваше имя.']),
                    new Length(['min' => 2, 'minMessage' => 'Имя должно содержать минимум {{ limit }} символа.']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Фамилия',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите вашу фамилию'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите вашу фамилию.']),
                    new Length(['min' => 2, 'minMessage' => 'Фамилия должна содержать минимум {{ limit }} символа.']),
                ],
            ])
            ->add('middleName', TextType::class, [
                'label' => 'Отчество',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите отчество'
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Пол',
                'choices' => [
                    'Выберите пол' => '',
                    'Мужской' => Gender::MALE,
                    'Женский' => Gender::FEMALE,
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите ваш пол.']),
                ],
            ])
            ->add('birthday', BirthdayType::class, [
                'label' => 'Дата рождения',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите дату рождения.']),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Номер телефона',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+7 (900) 123-45-67'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите номер телефона.']),
                    new Regex([
                        'pattern' => '/^\+?[0-9\s\(\)\-]+$/',
                        'message' => 'Некорректный формат номера телефона.'
                    ])
                ],
            ])
            ->add('passportSeries', TextType::class, [
                'label' => 'Серия паспорта',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '1234',
                    'maxlength' => '4'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите серию паспорта.']),
                    new Regex([
                        'pattern' => '/^\d{4}$/',
                        'message' => 'Серия паспорта должна состоять из 4 цифр.'
                    ])
                ],
            ])
            ->add('passportNumber', TextType::class, [
                'label' => 'Номер паспорта',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123456',
                    'maxlength' => '6'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите номер паспорта.']),
                    new Regex([
                        'pattern' => '/^\d{6}$/',
                        'message' => 'Номер паспорта должен состоять из 6 цифр.'
                    ])
                ],
            ])
            ->add('passportIssueDate', DateType::class, [
                'label' => 'Дата выдачи паспорта',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите дату выдачи паспорта.']),
                ],
            ])
            ->add('passportIssuedBy', TextType::class, [
                'label' => 'Кем выдан паспорт',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Наименование органа, выдавшего паспорт'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите кем выдан паспорт.']),
                ],
            ])
            ->add('addressCountry', CountryType::class, [
                'label' => 'Страна',
                'placeholder' => 'Выберите страну',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите страну.']),
                ],
                'preferred_choices' => ['RU'],
            ])
            ->add('addressRegion', TextType::class, [
                'label' => 'Регион/Область',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Например: Московская область'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите регион.']),
                ],
            ])
            ->add('addressLocality', TextType::class, [
                'label' => 'Населенный пункт',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Город, село, деревня и т.д.'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите населенный пункт.']),
                ],
            ])
            ->add('addressStreet', TextType::class, [
                'label' => 'Улица',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Название улицы'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите улицу.']),
                ],
            ])
            ->add('addressHouse', TextType::class, [
                'label' => 'Дом',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12А'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, укажите номер дома.']),
                ],
            ])
            ->add('addressBody', TextType::class, [
                'label' => 'Корпус/Строение',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '1 (если есть)'
                ],
            ])
            ->add('addressApartment', IntegerType::class, [
                'label' => 'Квартира/Офис',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123 (если есть)'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Сохранить профиль',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}