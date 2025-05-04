<?php

namespace App\Controller\Admin;

use App\Entity\Gallery;
use App\Form\GalleryFormType;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/gallery')]
class GalleryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private GalleryRepository $galleryRepository;
    private string $uploadDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        GalleryRepository $galleryRepository,
        string $uploadDirectory = 'uploads/gallery'
    ) {
        $this->entityManager = $entityManager;
        $this->galleryRepository = $galleryRepository;
        $this->uploadDirectory = $uploadDirectory;
    }

    #[Route('/', name: 'admin_gallery_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $category = $request->query->get('category');
        $limit = 25; // Items per page
        
        $pagination = $this->galleryRepository->findPaginated($page, $limit, $category);
        
        // Get all unique categories for filter dropdown
        $categories = $this->entityManager->createQuery(
            'SELECT DISTINCT g.category FROM App\Entity\Gallery g WHERE g.category IS NOT NULL ORDER BY g.category ASC'
        )->getResult();
        
        return $this->render('admin/gallery/index.html.twig', [
            'pagination' => $pagination,
            'categories' => $categories,
            'current_category' => $category,
        ]);
    }

    #[Route('/new', name: 'admin_gallery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $gallery = new Gallery();
        $form = $this->createForm(GalleryFormType::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', "Une erreur est survenue lors de l'upload de l'image.");
                    return $this->redirectToRoute('admin_gallery_new');
                }
                
                $gallery->setFilename($newFilename);
            }
            
            // Set position to the end if not specified
            if (!$gallery->getPosition()) {
                $lastPosition = $this->entityManager->createQuery(
                    'SELECT MAX(g.position) FROM App\Entity\Gallery g'
                )->getSingleScalarResult() ?? 0;
                
                $gallery->setPosition($lastPosition + 1);
            }
            
            $this->entityManager->persist($gallery);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Image ajoutée avec succès.');
            return $this->redirectToRoute('admin_gallery_index');
        }

        return $this->render('admin/gallery/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_gallery_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Gallery $gallery, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(GalleryFormType::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                // Delete old image if it exists
                if ($gallery->getFilename()) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadDirectory . '/' . $gallery->getFilename();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', "Une erreur est survenue lors de l'upload de l'image.");
                    return $this->redirectToRoute('admin_gallery_edit', ['id' => $gallery->getId()]);
                }
                
                $gallery->setFilename($newFilename);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Image modifiée avec succès.');
            return $this->redirectToRoute('admin_gallery_index');
        }

        return $this->render('admin/gallery/edit.html.twig', [
            'gallery' => $gallery,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_gallery_delete', methods: ['POST'])]
    public function delete(Request $request, Gallery $gallery): Response
    {
        if ($this->isCsrfTokenValid('delete'.$gallery->getId(), $request->request->get('_token'))) {
            // Delete image file
            if ($gallery->getFilename()) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadDirectory . '/' . $gallery->getFilename();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->entityManager->remove($gallery);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Image supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_gallery_index');
    }

    #[Route('/upload', name: 'admin_gallery_ajax_upload', methods: ['POST'])]
    public function ajaxUpload(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }
        
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
        
        try {
            $uploadedFile->move(
                $this->getParameter('kernel.project_dir') . '/public/' . $this->uploadDirectory,
                $newFilename
            );
            
            // Create new gallery entry
            $gallery = new Gallery();
            $gallery->setTitle($originalFilename);
            $gallery->setFilename($newFilename);
            
            // Set position to the end
            $lastPosition = $this->entityManager->createQuery(
                'SELECT MAX(g.position) FROM App\Entity\Gallery g'
            )->getSingleScalarResult() ?? 0;
            
            $gallery->setPosition($lastPosition + 1);
            
            $this->entityManager->persist($gallery);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'id' => $gallery->getId(),
                'filename' => $newFilename,
                'path' => '/' . $this->uploadDirectory . '/' . $newFilename,
            ]);
            
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Failed to upload file'], 500);
        }
    }

    #[Route('/reorder', name: 'admin_gallery_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $positions = json_decode($request->getContent(), true);
        
        if (!$positions) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }
        
        foreach ($positions as $position) {
            $id = $position['id'];
            $newPosition = $position['position'];
            
            $gallery = $this->galleryRepository->find($id);
            if ($gallery) {
                $gallery->setPosition($newPosition);
            }
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/list', name: 'admin_gallery_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $images = $this->galleryRepository->findBy(['isActive' => true], ['position' => 'ASC', 'createdAt' => 'DESC']);
        
        $data = [];
        foreach ($images as $image) {
            $data[] = [
                'id' => $image->getId(),
                'title' => $image->getTitle(),
                'filename' => $image->getFilename(),
                'description' => $image->getDescription(),
                'category' => $image->getCategory(),
                'updatedAt' => $image->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/{id}/json', name: 'admin_gallery_json', methods: ['GET'])]
    public function jsonDetail(Gallery $gallery): JsonResponse
    {
        return new JsonResponse([
            'id' => $gallery->getId(),
            'title' => $gallery->getTitle(),
            'filename' => $gallery->getFilename(),
            'description' => $gallery->getDescription(),
            'category' => $gallery->getCategory(),
            'updatedAt' => $gallery->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
