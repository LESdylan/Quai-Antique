<?php

namespace App\Service;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaLibraryService
{
    private $imageUploader;
    private $entityManager;
    private $imageRepository;
    
    public function __construct(
        ImageUploader $imageUploader,
        EntityManagerInterface $entityManager,
        ImageRepository $imageRepository
    ) {
        $this->imageUploader = $imageUploader;
        $this->entityManager = $entityManager;
        $this->imageRepository = $imageRepository;
    }
    
    /**
     * Upload an image to the media library
     */
    public function uploadImage(UploadedFile $file, array $metadata = []): Image
    {
        try {
            // Upload the file first
            $filename = $this->imageUploader->upload($file);
            
            // Create image entity
            $image = new Image();
            $image->setFilename($filename);
            $image->setOriginalFilename($file->getClientOriginalName());
            $image->setMimeType($file->getMimeType());
            $image->setFileSize($file->getSize());
            
            // Get image dimensions
            if (strpos($file->getMimeType(), 'image/') === 0) {
                $dimensions = getimagesize($this->imageUploader->getTargetDirectory() . '/' . $filename);
                if ($dimensions) {
                    $image->setDimensions($dimensions[0] . 'x' . $dimensions[1]);
                }
            }
            
            // Set other metadata
            if (isset($metadata['alt'])) {
                $image->setAlt($metadata['alt']);
            } else {
                // Default to filename without extension as alt text
                $image->setAlt(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            }
            
            if (isset($metadata['title'])) {
                $image->setTitle($metadata['title']);
            }
            
            if (isset($metadata['description'])) {
                $image->setDescription($metadata['description']);
            }
            
            if (isset($metadata['category'])) {
                $image->setCategory($metadata['category']);
            }
            
            if (isset($metadata['purpose'])) {
                $image->setPurpose($metadata['purpose']);
            }
            
            if (isset($metadata['dish'])) {
                $image->setDish($metadata['dish']);
            }
            
            // Save the image entity
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            
            return $image;
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }
    
    /**
     * Search images with pagination and filters
     */
    public function searchImages(array $criteria = [], int $page = 1, int $limit = 24): array
    {
        return $this->imageRepository->search($criteria, $page, $limit);
    }
    
    /**
     * Get recent images
     */
    public function getRecentImages(int $limit = 8): array
    {
        return $this->imageRepository->findBy(
            ['isActive' => true],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
    
    /**
     * Get images by category
     */
    public function getImagesByCategory(string $category, int $limit = null): array
    {
        return $this->imageRepository->findBy(
            ['category' => $category, 'isActive' => true],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}
