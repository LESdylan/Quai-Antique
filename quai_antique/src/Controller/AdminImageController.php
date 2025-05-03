<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use App\Repository\ImageRepository;
use App\Service\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/image')]
#[IsGranted('ROLE_ADMIN')]
class AdminImageController extends AbstractController
{
    #[Route('/', name: 'app_admin_image_index', methods: ['GET'])]
    public function index(ImageRepository $imageRepository): Response
    {
        // Use a simpler approach without relying on special columns
        $images = $imageRepository->findAll();
        
        return $this->render('admin/image/index.html.twig', [
            'images' => $images,
        ]);
    }

    #[Route('/new', name: 'app_admin_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageUploader $imageUploader): Response
    {
        $image = new Image();
        
        // Check if a purpose was specified in the query string
        if ($request->query->has('purpose')) {
            $image->setPurpose($request->query->get('purpose'));
        }
        
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Check if we're using a library image
                $selectedLibraryImageId = $request->request->get('selected_library_image');
                if ($selectedLibraryImageId) {
                    // Clone an existing image with a new purpose
                    $sourceImage = $entityManager->getRepository(Image::class)->find($selectedLibraryImageId);
                    if (!$sourceImage) {
                        throw new \Exception("Selected library image not found");
                    }
                    
                    // Copy properties from source image
                    $image->setFilename($sourceImage->getFilename());
                    $image->setOriginalFilename($sourceImage->getOriginalFilename());
                    $image->setMimeType($sourceImage->getMimeType());
                    
                    // Don't copy purpose or dish, those are set in the form
                } else {
                    // Handle regular file upload
                    // Get the upload method
                    $uploadMethod = $form->get('upload_method')->getData();
                    
                    if ($uploadMethod === 'upload') {
                        $file = $form->get('file')->getData();
                        if ($file) {
                            $fileName = $imageUploader->upload($file);
                            $image->setFilename($fileName);
                            $image->setOriginalFilename($file->getClientOriginalName());
                            $image->setMimeType($file->getMimeType());
                        } else {
                            $this->addFlash('error', 'Please select a file to upload.');
                            return $this->render('admin/image/new.html.twig', [
                                'image' => $image,
                                'form' => $form,
                            ]);
                        }
                    } else { // existing_path
                        $filePath = $form->get('existing_path')->getData();
                        if ($filePath && file_exists($filePath)) {
                            try {
                                $fileName = $imageUploader->upload($filePath);
                                $image->setFilename($fileName);
                                $image->setOriginalFilename(basename($filePath));
                                $image->setMimeType(mime_content_type($filePath));
                            } catch (\Exception $e) {
                                $this->addFlash('error', 'Error copying file: ' . $e->getMessage());
                                return $this->render('admin/image/new.html.twig', [
                                    'image' => $image,
                                    'form' => $form,
                                ]);
                            }
                        } else {
                            $this->addFlash('error', 'The specified file path does not exist.');
                            return $this->render('admin/image/new.html.twig', [
                                'image' => $image,
                                'form' => $form,
                            ]);
                        }
                    }
                }
                
                // Check if we need to clear existing purpose assignment
                $purpose = $image->getPurpose();
                if ($purpose) {
                    $existingImage = $entityManager->getRepository(Image::class)->findOneBy(['purpose' => $purpose]);
                    if ($existingImage) {
                        $existingImage->setPurpose(null);
                        $entityManager->persist($existingImage);
                    }
                }
                
                $entityManager->persist($image);
                $entityManager->flush();
    
                $this->addFlash('success', 'Image has been added successfully!');
                return $this->redirectToRoute('app_admin_image_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
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
            // Get the upload method
            $uploadMethod = $form->get('upload_method')->getData();
            
            if ($uploadMethod === 'upload') {
                $file = $form->get('file')->getData();
                if ($file) {
                    $fileName = $imageUploader->upload($file);
                    $image->setFilename($fileName);
                    $image->setOriginalFilename($file->getClientOriginalName());
                    $image->setMimeType($file->getMimeType());
                }
            } else { // existing_path
                $filePath = $form->get('existing_path')->getData();
                if ($filePath && file_exists($filePath)) {
                    try {
                        $fileName = $imageUploader->upload($filePath);
                        $image->setFilename($fileName);
                        $image->setOriginalFilename(basename($filePath));
                        $image->setMimeType(mime_content_type($filePath));
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Error copying file: ' . $e->getMessage());
                        return $this->render('admin/image/edit.html.twig', [
                            'image' => $image,
                            'form' => $form,
                        ]);
                    }
                } else if ($filePath) {
                    $this->addFlash('error', 'The specified file path does not exist.');
                    return $this->render('admin/image/edit.html.twig', [
                        'image' => $image,
                        'form' => $form,
                    ]);
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Image has been updated successfully!');
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
            // Get filename before removing entity
            $filename = $image->getFilename();
            
            // Remove from database
            $entityManager->remove($image);
            $entityManager->flush();
            
            // Remove file from filesystem
            try {
                $imageUploader->remove($filename);
            } catch (\Exception $e) {
                // Log error but don't show to user since DB entry is already deleted
                // TODO: Add logging here
            }
            
            $this->addFlash('success', 'Image has been deleted.');
        }

        return $this->redirectToRoute('app_admin_image_index');
    }
    
    #[Route('/{id}/toggle-active', name: 'app_admin_image_toggle_active', methods: ['POST'])]
    public function toggleActive(Image $image, EntityManagerInterface $entityManager): Response
    {
        $image->setIsActive(!$image->isIsActive());
        $entityManager->flush();
        
        $status = $image->isIsActive() ? 'activated' : 'deactivated';
        $this->addFlash('success', "Image has been $status.");
        
        return $this->redirectToRoute('app_admin_image_index');
    }
}
