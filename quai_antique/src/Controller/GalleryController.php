<?php

namespace App\Controller;

use App\Repository\GalleryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GalleryController extends AbstractController
{
    #[Route('/notre-cuisine', name: 'app_gallery')]
    public function index(GalleryRepository $galleryRepository): Response
    {
        // If the repository is already set up, get actual gallery images
        try {
            $images = $galleryRepository->findBy(['isActive' => true], ['position' => 'ASC']);
        } catch (\Exception $e) {
            // For development: if the repository or entity isn't ready yet
            $images = [];
        }
        
        return $this->render('gallery/index.html.twig', [
            'images' => $images,
        ]);
    }
}
