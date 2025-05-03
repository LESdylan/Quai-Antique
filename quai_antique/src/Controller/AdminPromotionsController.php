<?php

namespace App\Controller;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/promotions')]
#[IsGranted('ROLE_ADMIN')]
class AdminPromotionsController extends AbstractController
{
    #[Route('', name: 'app_admin_promotions')]
    public function index(PromotionRepository $promotionRepository): Response
    {
        $promotions = $promotionRepository->findAllOrdered();
        
        return $this->render('admin/promotions/index.html.twig', [
            'promotions' => $promotions,
        ]);
    }

    #[Route('/new', name: 'app_admin_promotions_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('promotions_directory'),
                        $newFilename
                    );
                    $promotion->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading your image.');
                }
            }

            $entityManager->persist($promotion);
            $entityManager->flush();

            $this->addFlash('success', 'Promotion created successfully!');
            return $this->redirectToRoute('app_admin_promotions');
        }

        return $this->render('admin/promotions/new.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_promotions_get', methods: ['GET'])]
    public function show(Promotion $promotion, Request $request): Response
    {
        // Handle AJAX request for editing
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'promotion' => [
                    'id' => $promotion->getId(),
                    'title' => $promotion->getTitle(),
                    'description' => $promotion->getDescription(),
                    'type' => $promotion->getType(),
                    'startDate' => $promotion->getStartDate()->format('Y-m-d'),
                    'endDate' => $promotion->getEndDate()->format('Y-m-d'),
                    'buttonText' => $promotion->getButtonText(),
                    'buttonLink' => $promotion->getButtonLink(),
                    'isActive' => $promotion->isIsActive(),
                    'imageFilename' => $promotion->getImageFilename(),
                ]
            ]);
        }
        
        return $this->render('admin/promotions/show.html.twig', [
            'promotion' => $promotion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_promotions_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Promotion $promotion,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('promotions_directory'),
                        $newFilename
                    );
                    
                    // Remove old file
                    $oldFilename = $promotion->getImageFilename();
                    if ($oldFilename) {
                        $oldFilePath = $this->getParameter('promotions_directory').'/'.$oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    $promotion->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading your image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Promotion updated successfully!');
            return $this->redirectToRoute('app_admin_promotions');
        }

        return $this->render('admin/promotions/edit.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_promotions_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Promotion $promotion,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$promotion->getId(), $request->request->get('_token'))) {
            // Remove image file if exists
            $filename = $promotion->getImageFilename();
            if ($filename) {
                $filePath = $this->getParameter('promotions_directory').'/'.$filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $entityManager->remove($promotion);
            $entityManager->flush();
            
            $this->addFlash('success', 'Promotion deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_promotions');
    }
    
    #[Route('/toggle-active', name: 'app_admin_promotions_toggle', methods: ['POST'])]
    public function toggleActive(
        Request $request, 
        EntityManagerInterface $entityManager,
        PromotionRepository $promotionRepository
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['promotionId']) || !isset($data['isActive'])) {
            return $this->json(['success' => false, 'message' => 'Invalid data'], 400);
        }
        
        $promotion = $promotionRepository->find($data['promotionId']);
        
        if (!$promotion) {
            return $this->json(['success' => false, 'message' => 'Promotion not found'], 404);
        }
        
        $promotion->setIsActive($data['isActive']);
        $entityManager->flush();
        
        return $this->json([
            'success' => true, 
            'message' => 'Promotion status updated successfully'
        ]);
    }
}
