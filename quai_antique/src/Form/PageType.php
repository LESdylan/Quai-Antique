<?php

namespace App\Form;

use App\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre de la page'],
            ])
            ->add('content', CKEditorType::class, [
                'label' => 'Contenu',
                'config' => [
                    'uiColor' => '#ffffff',
                    'toolbar' => 'full',
                    'height' => 400,
                ],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier cette page',
                'required' => false,
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Meta titre (SEO)',
                'required' => false,
                'attr' => ['placeholder' => 'Meta titre pour le référencement (optionnel)'],
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Meta description (SEO)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Meta description pour le référencement (optionnel)',
                    'rows' => 3,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
