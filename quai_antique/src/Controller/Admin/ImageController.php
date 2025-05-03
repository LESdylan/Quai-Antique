<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use App\Service\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/image')]
class ImageController extends AbstractController
{
    #[Route('/', name: 'app_admin_image_index', methods: ['GET'])]
    public function index(ImageRepository $imageRepository): Response
    {
        return $this->render('admin/image/index.html.twig', [
            'images' => $imageRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageUploader $imageUploader): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('file')->getData();
            
            if ($imageFile) {
                $fileName = $imageUploader->upload($imageFile);
                $image->setFilename($fileName);
                $image->setOriginalFilename($imageFile->getClientOriginalName());
                $image->setMimeType($imageFile->getMimeType());
                
                $entityManager->persist($image);
                $entityManager->flush();
                
                $this->addFlash('success', 'Image uploaded successfully');
                
                return $this->redirectToRoute('app_admin_image_index');
            }
        }

        return $this->render('admin/image/new.html.twig', [
            'image' => $image,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_image_show', methods: ['GET'])]
    public function show(Image $image): Response
    {
        return $this->render('admin/image/show.html.twig', [
            'image' => $image,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_image_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Image $image, EntityManagerInterface $entityManager, ImageUploader $imageUploader): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('file')->getData();
            
            if ($imageFile) {
                // Delete old file if it exists
                $oldFilePath = $imageUploader->getTargetDirectory() . '/' . $image->getFilename();
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                
                $fileName = $imageUploader->upload($imageFile);
                $image->setFilename($fileName);
                $image->setOriginalFilename($imageFile->getClientOriginalName());
                $image->setMimeType($imageFile->getMimeType());
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Image updated successfully');
            
            return $this->redirectToRoute('app_admin_image_index');
        }

        return $this->render('admin/image/edit.html.twig', [
            'image' => $image,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_image_delete', methods: ['POST'])]
    public function delete(Request $request, Image $image, EntityManagerInterface $entityManager, ImageUploader $imageUploader): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            // Delete image file
            $filePath = $imageUploader->getTargetDirectory() . '/' . $image->getFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $entityManager->remove($image);
            $entityManager->flush();
            
            $this->addFlash('success', 'Image deleted successfully');
        }

        return $this->redirectToRoute('app_admin_image_index');
    }
}
