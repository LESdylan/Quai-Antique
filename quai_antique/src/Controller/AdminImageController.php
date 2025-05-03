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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

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
    public function new(Request $request, ImageRepository $imageRepository, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if directory exists and create it if needed
            $filesystem = new Filesystem();
            $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
            
            // Create target directory if it doesn't exist
            if (!$filesystem->exists($targetDir)) {
                $filesystem->mkdir($targetDir, 0755);
            }
            
            // Handle uploads based on the method
            $uploadMethod = $form->get('upload_method')->getData();
            $fileUploaded = false;
            
            if ($uploadMethod === 'upload') {
                $file = $form->get('file')->getData();
                
                if ($file) {
                    try {
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                        
                        // Move file to the target directory
                        $file->move($targetDir, $newFilename);
                        
                        // Set image data
                        $image->setFilename($newFilename);
                        $image->setOriginalFilename($file->getClientOriginalName());
                        $image->setMimeType($file->getMimeType());
                        $fileUploaded = true;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        return $this->render('admin/image/new.html.twig', [
                            'image' => $image,
                            'form' => $form->createView(),
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $this->addFlash('error', 'Please select a file to upload.');
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => 'No file selected'
                    ]);
                }
            } elseif ($uploadMethod === 'path') {
                // Handle file path method - completely rewritten to avoid temp files
                $filePath = $form->get('file_path')->getData();
                
                if (!$filePath) {
                    $this->addFlash('error', 'No file path provided.');
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => 'No file path provided'
                    ]);
                }
                
                // Debug info
                $this->addFlash('info', 'Processing path: ' . $filePath);
                
                if (!file_exists($filePath)) {
                    $this->addFlash('error', 'File does not exist: ' . $filePath);
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => 'File not found'
                    ]);
                }
                
                if (!is_readable($filePath)) {
                    $this->addFlash('error', 'File is not readable: ' . $filePath);
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => 'File not readable'
                    ]);
                }
                
                try {
                    // Get file info directly
                    $originalFilename = basename($filePath);
                    $safeFilename = $slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
                    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                    $targetPath = $targetDir . '/' . $newFilename;
                    
                    // Read the file contents directly
                    $fileContents = @file_get_contents($filePath);
                    if ($fileContents === false) {
                        throw new \Exception('Failed to read file contents: ' . error_get_last()['message'] ?? 'Unknown error');
                    }
                    
                    // Write directly to the target location
                    $bytesWritten = @file_put_contents($targetPath, $fileContents);
                    if ($bytesWritten === false) {
                        throw new \Exception('Failed to write file: ' . error_get_last()['message'] ?? 'Unknown error');
                    }
                    
                    // Verify file was written successfully
                    if (!file_exists($targetPath)) {
                        throw new \Exception('Target file was not created');
                    }
                    
                    // Check the file size matches what we expected to write
                    if ($bytesWritten != strlen($fileContents)) {
                        throw new \Exception("File size mismatch: wrote $bytesWritten bytes but expected " . strlen($fileContents));
                    }
                    
                    // Get MIME type using the target file (not the source)
                    $mimeType = mime_content_type($targetPath);
                    
                    // Update the image entity
                    $image->setFilename($newFilename);
                    $image->setOriginalFilename($originalFilename);
                    $image->setMimeType($mimeType);
                    $fileUploaded = true;
                    
                    $this->addFlash('info', "File copied successfully to $targetPath");
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to process file: ' . $e->getMessage());
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($uploadMethod === 'existing') {
                // Handle existing image selection
                $existingImageId = $request->request->get('selected_library_image');
                if ($existingImageId) {
                    $existingImage = $imageRepository->find($existingImageId);
                    if ($existingImage && $existingImage->getFilename()) {
                        // Clone existing image data but create a new entity
                        $image->setFilename($existingImage->getFilename());
                        $image->setOriginalFilename($existingImage->getOriginalFilename());
                        $image->setMimeType($existingImage->getMimeType());
                        $fileUploaded = true;
                    } else {
                        $this->addFlash('error', 'Invalid image selection or selected image has no filename.');
                        return $this->render('admin/image/new.html.twig', [
                            'image' => $image,
                            'form' => $form->createView(),
                            'error' => 'Invalid image selection'
                        ]);
                    }
                } else {
                    $this->addFlash('error', 'No image was selected from the library.');
                    return $this->render('admin/image/new.html.twig', [
                        'image' => $image,
                        'form' => $form->createView(),
                        'error' => 'No image selected'
                    ]);
                }
            }

            // Verify we have a filename before saving
            if (!$fileUploaded || !$image->getFilename()) {
                $this->addFlash('error', 'No image was provided. Please upload a file or select one from the library.');
                return $this->render('admin/image/new.html.twig', [
                    'image' => $image,
                    'form' => $form->createView(),
                    'error' => 'No filename set'
                ]);
            }

            // Save the image entity to the database
            $image->setIsActive(true);
            $entityManager->persist($image);
            $entityManager->flush();

            $this->addFlash('success', 'Image added successfully!');
            return $this->redirectToRoute('app_admin_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/image/new.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
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
