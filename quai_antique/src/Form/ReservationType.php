<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Length;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Votre nom de famille'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
                    new Length(['max' => 64, 'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères']),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['placeholder' => 'Votre prénom'],
                'constraints' => [
                    new Length(['max' => 64, 'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre.email@exemple.com'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre adresse email']),
                    new Email(['message' => 'Veuillez entrer une adresse email valide']),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => '01 23 45 67 89'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre numéro de téléphone']),
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'js-datepicker'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une date']),
                ],
            ])
            ->add('timeSlot', HiddenType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un horaire']),
                ],
            ])
            ->add('guestCount', IntegerType::class, [
                'label' => 'Nombre de couverts',
                'attr' => ['min' => 1, 'max' => 10],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez indiquer le nombre de personnes']),
                    new Range([
                        'min' => 1, 
                        'max' => 10, 
                        'minMessage' => 'Au moins {{ limit }} personne requise',
                        'maxMessage' => 'Maximum {{ limit }} personnes par réservation en ligne'
                    ]),
                ],
            ])
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies ou restrictions alimentaires',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Merci de nous préciser vos allergies ou restrictions alimentaires',
                    'rows' => 3
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Informations complémentaires pour votre réservation',
                    'rows' => 3
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
