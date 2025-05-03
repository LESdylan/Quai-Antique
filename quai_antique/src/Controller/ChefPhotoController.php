<?php

namespace App\Controller;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/chef-photo')]
#[IsGranted('ROLE_ADMIN')]
class ChefPhotoController extends AbstractController
{
    #[Route('/import', name: 'app_admin_chef_photo_import')]
    public function importChefPhoto(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $errors = [];
        $success = false;
        
        if ($request->isMethod('POST')) {
            // Get the image path from request
            $filePath = $request->request->get('file_path');
            $title = $request->request->get('title', 'Chef Photo');
            $alt = $request->request->get('alt', 'Chef Photo');
            
            // Log the received path
            $this->addFlash('info', "Processing file: $filePath");
            
            // Validate path
            if (!file_exists($filePath)) {
                $errors[] = "File not found at path: $filePath";
            } elseif (!is_readable($filePath)) {
                $errors[] = "File exists but is not readable: $filePath";
            } else {
                try {
                    // Define target paths
                    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
                    if (!is_dir($targetDir)) {
                        if (!mkdir($targetDir, 0755, true)) {
                            $errors[] = "Failed to create upload directory";
                        }
                    }
                    
                    // Generate a safe filename
                    $originalFilename = basename($filePath);
                    $safeFilename = $slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
                    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
                    $targetPath = $targetDir . '/' . $newFilename;
                    
                    // Direct file copy
                    if (copy($filePath, $targetPath)) {
                        // Create image entity
                        $image = new Image();
                        $image->setFilename($newFilename);
                        $image->setOriginalFilename($originalFilename);
                        $image->setTitle($title);
                        $image->setAlt($alt);
                        $image->setCategory('chef');
                        $image->setPurpose('chef');
                        $image->setMimeType(mime_content_type($targetPath));
                        $image->setIsActive(true);
                        
                        $entityManager->persist($image);
                        $entityManager->flush();
                        
                        $this->addFlash('success', 'Chef photo imported successfully!');
                        $success = true;
                    } else {
                        $errors[] = "Failed to copy file to: $targetPath";
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Error: ' . $e->getMessage();
                }
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('admin/chef_photo/import.html.twig', [
            'errors' => $errors,
            'success' => $success
        ]);
    }
}
