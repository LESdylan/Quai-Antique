<?php

namespace App\Form;

use App\Entity\Schedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayName', ChoiceType::class, [
                'label' => 'Jour de la semaine',
                'choices' => [
                    'Lundi' => 'Lundi',
                    'Mardi' => 'Mardi',
                    'Mercredi' => 'Mercredi',
                    'Jeudi' => 'Jeudi',
                    'Vendredi' => 'Vendredi',
                    'Samedi' => 'Samedi',
                    'Dimanche' => 'Dimanche',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un jour de la semaine']),
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('dayNumber', IntegerType::class, [
                'label' => 'Numéro du jour (1=Lundi, 7=Dimanche)',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le numéro du jour']),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 7,
                    'class' => 'form-control'
                ]
            ])
            ->add('isClosed', CheckboxType::class, [
                'label' => 'Jour fermé',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
            ->add('lunchOpeningTime', TimeType::class, [
                'label' => 'Heure d\'ouverture du midi',
                'required' => false,
                'widget' => 'choice',
                'attr' => [
                    'class' => 'time-field-container'
                ]
            ])
            ->add('lunchClosingTime', TimeType::class, [
                'label' => 'Heure de fermeture du midi',
                'required' => false,
                'widget' => 'choice',
                'attr' => [
                    'class' => 'time-field-container'
                ]
            ])
            ->add('dinnerOpeningTime', TimeType::class, [
                'label' => 'Heure d\'ouverture du soir',
                'required' => false,
                'widget' => 'choice',
                'attr' => [
                    'class' => 'time-field-container'
                ]
            ])
            ->add('dinnerClosingTime', TimeType::class, [
                'label' => 'Heure de fermeture du soir',
                'required' => false,
                'widget' => 'choice',
                'attr' => [
                    'class' => 'time-field-container'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Schedule::class,
        ]);
    }
}
