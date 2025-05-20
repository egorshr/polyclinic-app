<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Photographer;
use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BookingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Имя не может быть пустым']),
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => 'Имя должно содержать минимум {{ limit }} символа'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[А-яЁёA-Za-z\s\-]+$/u',
                        'message' => 'Имя может содержать только буквы, пробелы и дефисы'
                    ])
                ],
                'label' => 'Имя клиента',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Введите имя клиента'
                ]
            ])
            ->add('service', ChoiceType::class, [
                'choices' => array_combine(Service::getAvailableServices(), Service::getAvailableServices()),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Услуга не выбрана'])
                ],
                'label' => 'Услуга',
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('photographer', ChoiceType::class, [
                'choices' => array_combine(Photographer::getAvailablePhotographers(), Photographer::getAvailablePhotographers()),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Фотограф не выбран'])
                ],
                'label' => 'Фотограф',
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Дата не может быть пустой']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Дата не может быть в прошлом'
                    ])
                ],
                'label' => 'Дата съёмки',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Забронировать',
                'attr' => ['class' => 'btn btn-primary mt-3 w-100']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}