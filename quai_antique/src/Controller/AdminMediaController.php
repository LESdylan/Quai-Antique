<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Tag;
use App\Form\ImageType;
use App\Form\MediaSearchType;
use App\Repository\ImageRepository;
use App\Repository\TagRepository;
use App\Service\MediaLibraryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media')]
#[IsGranted('ROLE_ADMIN')]
class AdminMediaController extends AbstractController
{
    #[Route('/', name: 'app_admin_media')]
    public function index(Request $request, ImageRepository $imageRepository, TagRepository $tagRepository): Response
    {
        // Get all available categories and tags for filtering
        $categories = $imageRepository->findAllCategories();
        $tags = $tagRepository->findAll();
        
        // Build search form
        $searchForm = $this->createForm(MediaSearchType::class);
        $searchForm->handleRequest($request);
        
        $criteria = [];
        $page = $request->query->getInt('page', 1);
        
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            
            if (!empty($data['category'])) {
                $criteria['category'] = $data['category'];
            }
            
            if (!empty($data['tag'])) {
                $criteria['tag'] = $data['tag'];
            }
            
            if (!empty($data['search'])) {
                $criteria['search'] = $data['search'];
            }
            
            if (isset($data['isActive'])) {
                $criteria['isActive'] = $data['isActive'];
            }
        }
        
        // Get images with pagination
        $result = $imageRepository->search($criteria, $page, 24);
        
        return $this->render('admin/media/index.html.twig', [
            'images' => $result['images'],
            'pagination' => [
                'totalItems' => $result['totalItems'],
                'totalPages' => $result['totalPages'],
                'currentPage' => $result['currentPage'],
                'limit' => $result['limit'],
            ],
            'categories' => $categories,
            'tags' => $tags,
            'searchForm' => $searchForm->createView(),
        ]);
    }
    
    #[Route('/upload', name: 'app_admin_media_upload')]
    public function upload(Request $request, MediaLibraryService $mediaLibraryService): Response
    {
        $form = $this->createForm(ImageType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $file = $form->get('file')->getData();
                
                if ($file) {
                    $metadata = [
                        'alt' => $form->get('alt')->getData(),
                        'title' => $form->get('title')->getData(),
                        'description' => $form->get('description')->getData(),
                        'category' => $form->get('category')->getData(),
                    ];
                    
                    if ($form->has('purpose')) {
                        $metadata['purpose'] = $form->get('purpose')->getData();
                    }
                    
                    if ($form->has('dish')) {
                        $metadata['dish'] = $form->get('dish')->getData();
                    }
                    
                    $image = $mediaLibraryService->uploadImage($file, $metadata);
                    
                    $this->addFlash('success', 'Image uploaded successfully!');
                    
                    // Handle AJAX uploads
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'image' => [
                                'id' => $image->getId(),
                                'filename' => $image->getFilename(),
                                'url' => '/uploads/images/' . $image->getFilename(),
                                'alt' => $image->getAlt(),
                                'title' => $image->getTitle(),
                            ],
                        ]);
                    }
                    
                    return $this->redirectToRoute('app_admin_media');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 400);
                }
            }
        }
        
        return $this->render('admin/media/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/selector', name: 'app_admin_media_selector')]
    public function selector(Request $request, ImageRepository $imageRepository): Response
    {
        $mode = $request->query->get('mode', 'single');
        $callback = $request->query->get('callback', 'handleSelectedImages');
        $targetId = $request->query->get('targetId');
        
        $page = $request->query->getInt('page', 1);
        $result = $imageRepository->search(['isActive' => true], $page, 24);
        
        return $this->render('admin/media/selector.html.twig', [
            'images' => $result['images'],
            'pagination' => [
                'totalItems' => $result['totalItems'],
                'totalPages' => $result['totalPages'],
                'currentPage' => $result['currentPage'],
                'limit' => $result['limit'],
            ],
            'mode' => $mode,
            'callback' => $callback,
            'targetId' => $targetId,
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_media_show')]
    public function show(Image $image): Response
    {
        return $this->render('admin/media/show.html.twig', [
            'image' => $image,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_admin_media_edit')]
    public function edit(Request $request, Image $image, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Image updated successfully!');
            return $this->redirectToRoute('app_admin_media');
        }
        
        return $this->render('admin/media/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_media_delete', methods: ['POST'])]
    public function delete(Request $request, Image $image, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager->remove($image);
            $entityManager->flush();
            
            $this->addFlash('success', 'Image deleted successfully!');
        }
        
        return $this->redirectToRoute('app_admin_media');
    }
}
