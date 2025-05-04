<?php

namespace App\Form;

use App\Entity\Gallery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class GalleryFormType extends AbstractType
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
            ->add('imageFile', FileType::class, [
                'label' => 'Image (JPEG, PNG)',
                'mapped' => false,
                'required' => $options['require_image'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG or PNG)',
                    ])
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gallery::class,
            'require_image' => true,
        ]);
    }
}
