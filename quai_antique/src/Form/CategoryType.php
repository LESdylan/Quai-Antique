<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nom de catégorie']),
                ],
                'attr' => ['placeholder' => 'Entrer le nom de la catégorie']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de la catégorie (optionnel)',
                    'rows' => 3
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ordre d\'affichage',
                    'min' => 1
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'data' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
