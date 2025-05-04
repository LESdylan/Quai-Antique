<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre adresse email']),
                    new Email(['message' => 'Veuillez entrer une adresse email valide']),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un sujet']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre message']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
