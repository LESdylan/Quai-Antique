<?php

namespace App\Controller;

use App\Repository\GalleryRepository;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GalleryController extends AbstractController
{
    #[Route('/notre-cuisine', name: 'app_gallery')]
    public function index(GalleryRepository $galleryRepository, ImageRepository $imageRepository): Response
    {
        // First try to get images from the image table
        $images = $imageRepository->findByCategory('gallery');
        
        // If no images in the new system, try to get them from the old gallery table
        if (empty($images)) {
            try {
                $legacyImages = $galleryRepository->findBy(['isActive' => true], ['position' => 'ASC']);
                
                // Map legacy images to fit the expected format in templates
                $images = array_map(function($galleryImage) {
                    return [
                        'id' => $galleryImage->getId(),
                        'title' => $galleryImage->getTitle(),
                        'filename' => $galleryImage->getFilename(),
                        'description' => $galleryImage->getDescription(),
                        'category' => 'gallery'
                    ];
                }, $legacyImages);
            } catch (\Exception $e) {
                $images = [];
            }
        }
        
        return $this->render('gallery/index.html.twig', [
            'images' => $images,
        ]);
    }
}
