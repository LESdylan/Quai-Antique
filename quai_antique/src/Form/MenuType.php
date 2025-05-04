<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Gallery;
use App\Entity\Menu;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un titre']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 100,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => true,
                'placeholder' => 'Sélectionnez une catégorie',
            ])
            ->add('mealType', ChoiceType::class, [
                'label' => 'Type de repas',
                'choices' => [
                    'Entrée' => 'starter',
                    'Plat principal' => 'main',
                    'Dessert' => 'dessert',
                    'Boisson' => 'drink',
                ],
                'required' => true,
                'empty_data' => 'main', // Default value for the form
                'data' => $options['data']->getMealType() ?: 'main', // Use current value or default
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => ['min' => 1],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            // Replace file upload with media gallery selection
            ->add('image', EntityType::class, [
                'class' => Gallery::class,
                'choice_label' => 'title',
                'label' => 'Image du menu',
                'required' => false,
                'placeholder' => 'Sélectionnez une image...',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->where('g.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('g.title', 'ASC');
                },
                'attr' => [
                    'class' => 'media-gallery-selector',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
