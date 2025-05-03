<?php

namespace App\Form;

use App\Entity\Dish;
use App\Entity\Image;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upload_method', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Upload Method',
                'choices' => [
                    'Upload file' => 'upload',
                    'Use existing file' => 'existing',
                ],
                'expanded' => true,
                'data' => 'upload',
            ])
            ->add('file', FileType::class, [
                'label' => 'Image File',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
            ->add('existing_path', TextType::class, [
                'mapped' => false,
                'label' => 'Path to existing file',
                'required' => false,
                'attr' => [
                    'placeholder' => '/home/dlesieur/Documents/Studi/Quai-Antique/quai_antique/assets/gallery/your-image.jpg',
                    'class' => 'form-control',
                ],
                'help' => 'Enter the full path to an image file on the server',
            ])
            ->add('alt', TextType::class, [
                'label' => 'Alt Text (for accessibility)',
                'required' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'Image Title',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => false,
                'choices' => [
                    'Gallery' => 'gallery',
                    'Dish' => 'dish',
                    'Menu' => 'menu',
                    'Interior' => 'interior',
                    'Chef' => 'chef',
                ],
                'placeholder' => 'Select a category',
            ])
            ->add('purpose', ChoiceType::class, [
                'label' => 'Special Purpose',
                'required' => false,
                'choices' => [
                    'General Image' => null,
                    'Hero Banner (Homepage)' => 'hero_banner',
                    'Reservation Page' => 'reservation_page',
                    'Menu Header' => 'menu_header',
                    'Gallery Featured' => 'gallery_featured',
                    'About Us Section' => 'about_us',
                ],
                'help' => 'Designate this image for a special purpose on the website',
            ])
            ->add('dish', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => 'name',
                'placeholder' => 'No associated dish',
                'required' => false,
            ])
            ->add('isActive', null, [
                'label' => 'Active?',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
