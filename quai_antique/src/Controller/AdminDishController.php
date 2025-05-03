<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\Image;
use App\Form\DishType;
use App\Repository\DishRepository;
use App\Repository\ImageRepository;
use App\Service\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/dish')]
#[IsGranted('ROLE_ADMIN')]
class AdminDishController extends AbstractController
{
    #[Route('/new', name: 'app_admin_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageUploader $imageUploader, ImageRepository $imageRepository): Response
    {
        $dish = new Dish();
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set creation and update dates
                $dish->setCreatedAt(new \DateTime());
                $dish->setUpdatedAt(new \DateTime());
                
                // 1. Handle direct file uploads
                $newImages = $form->get('newImages')->getData();
                if ($newImages) {
                    foreach ($newImages as $newImage) {
                        try {
                            $imageName = $imageUploader->upload($newImage);
                            
                            $image = new Image();
                            $image->setFilename($imageName);
                            $image->setOriginalFilename($newImage->getClientOriginalName());
                            $image->setMimeType($newImage->getMimeType());
                            $image->setAlt($dish->getName());
                            $image->setCategory('dish');
                            $image->setDish($dish);
                            
                            $entityManager->persist($image);
                            $dish->addImage($image);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                        }
                    }
                }
                
                // 2. Handle images selected from media library
                $selectedMediaIds = $form->get('selectedMediaIds')->getData();
                if ($selectedMediaIds) {
                    $ids = explode(',', $selectedMediaIds);
                    foreach ($ids as $id) {
                        if (!empty($id)) {
                            $image = $imageRepository->find($id);
                            if ($image) {
                                // Clone the image or link existing one
                                // Option 1: Link existing image to this dish
                                $image->setDish($dish);
                                $image->setCategory('dish');
                                
                                // Option 2: Clone image to create a new copy
                                // $newImage = new Image();
                                // $newImage->setFilename($image->getFilename());
                                // $newImage->setOriginalFilename($image->getOriginalFilename());
                                // $newImage->setMimeType($image->getMimeType());
                                // $newImage->setAlt($dish->getName());
                                // $newImage->setCategory('dish');
                                // $newImage->setDish($dish);
                                // $entityManager->persist($newImage);
                            }
                        }
                    }
                }
                
                // Persist the dish to the database
                $entityManager->persist($dish);
                $entityManager->flush();
                
                $this->addFlash('success', 'Le plat a été créé avec succès.');
                return $this->redirectToRoute('app_admin_menu');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de la création du plat: ' . $e->getMessage());
            }
        } else if ($form->isSubmitted()) {
            // If form submission failed, display the errors
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
        
        return $this->render('admin/menu/dish_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_admin_dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager, ImageUploader $imageUploader): Response
    {
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Update updated date
            $dish->setUpdatedAt(new \DateTime());
            
            // Handle multiple image uploads
            $newImages = $form->get('newImages')->getData();
            
            if ($newImages) {
                foreach ($newImages as $newImage) {
                    $imageName = $imageUploader->upload($newImage);
                    
                    $image = new Image();
                    $image->setFilename($imageName);
                    $image->setOriginalFilename($newImage->getClientOriginalName());
                    $image->setMimeType($newImage->getMimeType());
                    $image->setAlt($dish->getName());
                    $image->setCategory('dish');
                    $image->setDish($dish);
                    
                    $entityManager->persist($image);
                    $dish->addImage($image);
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Le plat a été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_menu');
        }
        
        return $this->render('admin/menu/dish_form.html.twig', [
            'form' => $form->createView(),
            'dish' => $dish,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dish->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dish);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le plat a été supprimé.');
        }
        
        return $this->redirectToRoute('app_admin_menu');
    }
    
    #[Route('/toggle-status', name: 'app_admin_dish_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, EntityManagerInterface $entityManager, DishRepository $dishRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$this->isCsrfTokenValid('dish_toggle_status', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }
        
        $dishId = $data['dishId'] ?? null;
        $isActive = $data['isActive'] ?? null;
        
        if (!$dishId || $isActive === null) {
            return $this->json(['success' => false, 'message' => 'Missing required data'], 400);
        }
        
        $dish = $dishRepository->find($dishId);
        
        if (!$dish) {
            return $this->json(['success' => false, 'message' => 'Dish not found'], 404);
        }
        
        $dish->setIsActive($isActive);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/reorder', name: 'app_admin_dish_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('dish_reorder', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }
        
        // In a real application, you would implement dish reordering here
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/remove-image', name: 'app_admin_dish_remove_image', methods: ['POST'])]
    public function removeImage(Request $request, EntityManagerInterface $entityManager, ImageRepository $imageRepository): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $imageId = $content['imageId'] ?? null;
        
        if (!$imageId) {
            return new JsonResponse(['success' => false, 'message' => 'Image ID is required'], 400);
        }
        
        $image = $imageRepository->find($imageId);
        if (!$image) {
            return new JsonResponse(['success' => false, 'message' => 'Image not found'], 404);
        }
        
        // Check CSRF token
        if (!$this->isCsrfTokenValid('remove_image', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        
        // Remove image
        try {
            $entityManager->remove($image);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/set-main-image', name: 'app_admin_dish_set_main_image', methods: ['POST'])]
    public function setMainImage(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$this->isCsrfTokenValid('set_main_image', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }
        
        $imageId = $data['imageId'] ?? null;
        $dishId = $data['dishId'] ?? null;
        
        if (!$imageId || !$dishId) {
            return $this->json(['success' => false, 'message' => 'Missing required data'], 400);
        }
        
        $image = $entityManager->getRepository(Image::class)->find($imageId);
        $dish = $entityManager->getRepository(Dish::class)->find($dishId);
        
        if (!$image || !$dish) {
            return $this->json(['success' => false, 'message' => 'Image or dish not found'], 404);
        }
        
        // In a real app, you would set a mainImage relationship or a flag on the selected image
        // For this example, we'll just return success
        
        return $this->json(['success' => true]);
    }
}
