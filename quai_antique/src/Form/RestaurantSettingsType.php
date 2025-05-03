<?php

namespace App\Form;

use App\Entity\Restaurant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\Image;

class RestaurantSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du restaurant',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-control'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'URL Facebook',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'URL Instagram',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tripadvisorUrl', UrlType::class, [
                'label' => 'URL Tripadvisor',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('averagePriceLunch', MoneyType::class, [
                'label' => 'Prix moyen déjeuner',
                'required' => false,
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('averagePriceDinner', MoneyType::class, [
                'label' => 'Prix moyen dîner',
                'required' => false,
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG or PNG)',
                    ])
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('displayOpeningHours', CheckboxType::class, [
                'label' => 'Afficher les horaires d\'ouverture',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Restaurant::class,
        ]);
    }
}
