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

#[Route('/admin/api')]
#[IsGranted('ROLE_ADMIN')]
class AdminApiController extends AbstractController
{
    #[Route('/media', name: 'app_admin_api_media', methods: ['GET'])]
    public function getMedia(Request $request, ImageRepository $imageRepository): JsonResponse
    {
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
    public function setImagePurpose(Request $request, Image $image, ImageRepository $imageRepository): JsonResponse
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
            $imageRepository->save($existingImage, true);
        }
        
        // Set purpose on this image
        $image->setPurpose($purpose);
        $imageRepository->save($image, true);
        
        return new JsonResponse([
            'message' => 'Purpose updated successfully',
            'image' => [
                'id' => $image->getId(),
                'purpose' => $image->getPurpose()
            ]
        ]);
    }
}
