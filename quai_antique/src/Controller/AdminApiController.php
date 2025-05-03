<?php

namespace App\Controller;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin/api')]
#[IsGranted('ROLE_ADMIN')]
class AdminApiController extends AbstractController
{
    #[Route('/media', name: 'app_admin_api_media', methods: ['GET'])]
    public function getMedia(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        try {
            // Get query parameters for filtering
            $category = $request->query->get('category');
            $search = $request->query->get('search');
            $sort = $request->query->get('sort', 'newest'); // Default sort by newest
            
            // Build query criteria
            $criteria = [];
            if ($category && $category !== 'all') {
                $criteria['category'] = $category;
            }
            
            // Get images
            $images = $imageRepository->findBy($criteria, ['createdAt' => 'DESC']);
            
            // Filter by search term if provided
            if ($search) {
                $search = strtolower($search);
                $images = array_filter($images, function($image) use ($search) {
                    return (
                        stripos($image->getTitle() ?? '', $search) !== false ||
                        stripos($image->getAlt() ?? '', $search) !== false ||
                        stripos($image->getDescription() ?? '', $search) !== false
                    );
                });
            }
            
            // Sort results
            if ($sort === 'oldest') {
                usort($images, function($a, $b) {
                    return $a->getCreatedAt() <=> $b->getCreatedAt();
                });
            } elseif ($sort === 'name-asc') {
                usort($images, function($a, $b) {
                    return strcmp($a->getTitle() ?? $a->getAlt() ?? '', $b->getTitle() ?? $b->getAlt() ?? '');
                });
            } elseif ($sort === 'name-desc') {
                usort($images, function($a, $b) {
                    return strcmp($b->getTitle() ?? $b->getAlt() ?? '', $a->getTitle() ?? $a->getAlt() ?? '');
                });
            }
            
            // Convert to array of data
            $data = array_map(function($image) {
                return [
                    'id' => $image->getId(),
                    'title' => $image->getTitle(),
                    'alt' => $image->getAlt(),
                    'description' => $image->getDescription(),
                    'filename' => $image->getFilename(),
                    'category' => $image->getCategory(),
                    'purpose' => $image->getPurpose(),
                    'isActive' => $image->isIsActive(),
                    'createdAt' => $image->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $images);
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error in getMedia: ' . $e->getMessage());
            
            // Return a user-friendly error response
            return new JsonResponse([
                'error' => true,
                'message' => 'Failed to load media library. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/media/{id}', name: 'app_admin_api_media_get', methods: ['GET'])]
    public function getMediaItem(Image $image): JsonResponse
    {
        return new JsonResponse([
            'id' => $image->getId(),
            'title' => $image->getTitle(),
            'alt' => $image->getAlt(),
            'description' => $image->getDescription(),
            'filename' => $image->getFilename(),
            'category' => $image->getCategory(),
            'purpose' => $image->getPurpose(),
            'isActive' => $image->isIsActive(),
            'createdAt' => $image->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }
    
    #[Route('/media/{id}/purpose', name: 'app_admin_api_media_set_purpose', methods: ['POST'])]
    public function setImagePurpose(Request $request, Image $image, ImageRepository $imageRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $purpose = $data['purpose'] ?? null;
        
        if (!$purpose) {
            return new JsonResponse(['message' => 'Purpose is required'], Response::HTTP_BAD_REQUEST);
        }
        
        // If another image has this purpose, remove it
        $existingImage = $imageRepository->findOneBy(['purpose' => $purpose]);
        if ($existingImage && $existingImage->getId() !== $image->getId()) {
            $existingImage->setPurpose(null);
            // Replace repository save method with EntityManager
            $entityManager->persist($existingImage);
            $entityManager->flush();
        }
        
        // Set purpose on this image
        $image->setPurpose($purpose);
        // Replace repository save method with EntityManager
        $entityManager->persist($image);
        $entityManager->flush();
        
        return new JsonResponse([
            'message' => 'Purpose updated successfully',
            'image' => [
                'id' => $image->getId(),
                'purpose' => $image->getPurpose()
            ]
        ]);
    }

    #[Route('/verify-path', name: 'app_admin_api_verify_path', methods: ['POST'])]
    public function verifyFilePath(Request $request): JsonResponse
    {
        // Get the path from request
        $data = json_decode($request->getContent(), true);
        $path = $data['path'] ?? null;
        
        if (!$path) {
            return new JsonResponse([
                'exists' => false,
                'error' => 'No path provided'
            ]);
        }
        
        // Check if file exists and is readable
        if (file_exists($path) && is_readable($path)) {
            // Get MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($path);
            
            // Check if it's an image
            $isImage = strpos($mimeType, 'image/') === 0;
            
            // For images, generate a data URI for preview
            $preview = null;
            if ($isImage && filesize($path) < 5 * 1024 * 1024) { // Only for files less than 5MB
                $imageData = base64_encode(file_get_contents($path));
                $preview = 'data:' . $mimeType . ';base64,' . $imageData;
            }
            
            return new JsonResponse([
                'exists' => true,
                'mime_type' => $mimeType,
                'preview' => $preview,
                'is_image' => $isImage
            ]);
        }
        
        return new JsonResponse([
            'exists' => false,
            'error' => 'File does not exist or is not readable'
        ]);
    }
}
