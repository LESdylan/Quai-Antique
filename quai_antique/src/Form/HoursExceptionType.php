<?php

namespace App\Form;

use App\Entity\HoursException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HoursExceptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Exemple: Jour férié, Événement spécial...'],
            ])
            ->add('isClosed', CheckboxType::class, [
                'label' => 'Le restaurant est fermé ce jour',
                'required' => false,
            ])
            ->add('openingTime', TextType::class, [
                'label' => 'Heure d\'ouverture',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '10:00',
                    'pattern' => '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$',
                ],
                'help' => 'Format: HH:MM',
            ])
            ->add('closingTime', TextType::class, [
                'label' => 'Heure de fermeture',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '22:00',
                    'pattern' => '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$',
                ],
                'help' => 'Format: HH:MM',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HoursException::class,
        ]);
    }
}
