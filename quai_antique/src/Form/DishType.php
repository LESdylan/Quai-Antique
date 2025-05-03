<?php

namespace App\Form;

use App\Entity\Allergen;
use App\Entity\Category;
use App\Entity\Dish;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class DishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'required' => true,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => true,
            ])
            ->add('allergens', EntityType::class, [
                'class' => Allergen::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Allergènes',
                'attr' => [
                    'class' => 'select2',
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('isSeasonal', CheckboxType::class, [
                'label' => 'De saison',
                'required' => false,
            ])
            ->add('isVegetarian', CheckboxType::class, [
                'label' => 'Végétarien',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('isVegan', CheckboxType::class, [
                'label' => 'Végan',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('isGlutenFree', CheckboxType::class, [
                'label' => 'Sans gluten',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('newImages', FileType::class, [
                'label' => 'Images du plat',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WEBP)',
                            ])
                        ]
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('selectedMediaIds', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Mis en avant',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dish::class,
        ]);
    }
}
