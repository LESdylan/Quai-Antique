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
                    'Upload new file' => 'upload',
                    'Use existing file path' => 'path', 
                    'Select from library' => 'existing',
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'upload',
            ])
            ->add('file', FileType::class, [
                'label' => 'Image file',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
            ->add('file_path', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'File Path',
                'attr' => [
                    'placeholder' => 'Enter full path to the image file',
                    'class' => 'form-control'
                ],
                'help' => 'For example: /path/to/your/image.jpg'
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
                'required' => false,
                'choices' => [
                    'Hero Banner' => 'hero_banner',
                    'Quote Background' => 'quote_background',
                    'Reservation Background' => 'reservation_background',
                    'Logo' => 'logo',
                    'Background Image' => 'background',
                    'Gallery Image' => 'gallery',
                    'Dish Image' => 'dish',
                    'Chef Image' => 'chef',
                    'Interior Photo' => 'interior',
                    // Add other options as needed
                ],
                'placeholder' => 'None (general purpose)',
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
