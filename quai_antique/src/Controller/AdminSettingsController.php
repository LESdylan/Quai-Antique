<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\HoursException;
use App\Form\RestaurantSettingsType;
use App\Form\HoursExceptionType;
use App\Repository\HoursRepository;
use App\Repository\RestaurantRepository;
use App\Repository\HoursExceptionRepository;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class AdminSettingsController extends AbstractController
{
    #[Route('', name: 'app_admin_settings')]
    public function index(
        Request $request, 
        EntityManagerInterface $entityManager, 
        RestaurantRepository $restaurantRepository,
        HoursRepository $hoursRepository,
        SluggerInterface $slugger,
        ImageRepository $imageRepository
    ): Response {
        // Get or create restaurant info
        $restaurant = $restaurantRepository->findOneBy([]) ?? new Restaurant();
        $hours = $hoursRepository->findAllOrdered();
        
        $form = $this->createForm(RestaurantSettingsType::class, $restaurant);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle logo upload
            $logoFile = $form->get('logoFile')->getData();
            
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
                
                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    
                    // Update restaurant entity with new logo filename
                    $restaurant->setLogoFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading your logo');
                }
            }
            
            $entityManager->persist($restaurant);
            $entityManager->flush();
            
            $this->addFlash('success', 'Restaurant settings updated successfully!');
            
            return $this->redirectToRoute('app_admin_settings');
        }
        
        // Get hero image
        $heroImage = $imageRepository->findOneBy(['purpose' => 'hero_banner', 'isActive' => true]);
        if (!$heroImage) {
            $heroImage = $imageRepository->findOneBy(['purpose' => 'hero', 'isActive' => true]);
        }
        
        // Get quote background image
        $quoteBackgroundImage = $imageRepository->findOneBy(['purpose' => 'quote_background', 'isActive' => true]);
        
        // Get reservation background image
        $reservationBackgroundImage = $imageRepository->findOneBy(['purpose' => 'reservation_background', 'isActive' => true]);
        if (!$reservationBackgroundImage) {
            $reservationBackgroundImage = $imageRepository->findOneBy(['purpose' => 'Reservation Background', 'isActive' => true]);
        }
        
        // Get chef image
        $chefImage = $imageRepository->findOneBy(['purpose' => 'chef', 'isActive' => true]);
        
        return $this->render('admin/settings/index.html.twig', [
            'restaurant_form' => $form->createView(),
            'restaurant' => $restaurant,
            'hours' => $hours,
            'hero_image' => $heroImage,
            'quote_background_image' => $quoteBackgroundImage,
            'reservation_background_image' => $reservationBackgroundImage,
            'chef_image' => $chefImage,
        ]);
    }
    
    #[Route('/hours', name: 'app_admin_hours')]
    public function hours(HoursRepository $hoursRepository, HoursExceptionRepository $hoursExceptionRepository): Response
    {
        $hours = $hoursRepository->findAllOrdered();
        $exceptions = $hoursExceptionRepository->findAll();
        
        return $this->render('admin/settings/hours.html.twig', [
            'hours' => $hours,
            'hours_exceptions' => $exceptions,
        ]);
    }
    
    #[Route('/hours/{id}/edit', name: 'app_admin_hours_edit')]
    public function editHours(
        Request $request,
        int $id,
        HoursRepository $hoursRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $hour = $hoursRepository->find($id);
        
        if (!$hour) {
            throw $this->createNotFoundException('No hours found for id '.$id);
        }
        
        if ($request->isMethod('POST')) {
            $isClosed = (bool) $request->request->get('is_closed', false);
            $hour->setIsClosed($isClosed);
            
            if (!$isClosed) {
                // Format: HH:MM
                $lunchOpening = $request->request->get('lunch_opening');
                $lunchClosing = $request->request->get('lunch_closing');
                $dinnerOpening = $request->request->get('dinner_opening');
                $dinnerClosing = $request->request->get('dinner_closing');
                
                // Parse time strings into DateTimeInterface
                $hour->setLunchOpeningTime($lunchOpening ? new \DateTime($lunchOpening) : null);
                $hour->setLunchClosingTime($lunchClosing ? new \DateTime($lunchClosing) : null);
                $hour->setDinnerOpeningTime($dinnerOpening ? new \DateTime($dinnerOpening) : null);
                $hour->setDinnerClosingTime($dinnerClosing ? new \DateTime($dinnerClosing) : null);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Les horaires ont été mis à jour avec succès.');
            
            return $this->redirectToRoute('app_admin_hours');
        }
        
        return $this->render('admin/settings/hours_edit.html.twig', [
            'hour' => $hour,
        ]);
    }

    // Add new hours exception
    #[Route('/hours/exception/new', name: 'app_admin_hours_exception_new')]
    public function newHoursException(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $exception = new HoursException();
        $form = $this->createForm(HoursExceptionType::class, $exception);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($exception);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'exception d\'horaire a été créée avec succès.');
            return $this->redirectToRoute('app_admin_hours');
        }
        
        return $this->render('admin/settings/hours_exception_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    // Edit hours exception
    #[Route('/hours/exception/{id}/edit', name: 'app_admin_hours_exception_edit')]
    public function editHoursException(
        Request $request,
        HoursException $exception,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(HoursExceptionType::class, $exception);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'exception d\'horaire a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_hours');
        }
        
        return $this->render('admin/settings/hours_exception_edit.html.twig', [
            'form' => $form->createView(),
            'exception' => $exception,
        ]);
    }
    
    // Delete hours exception
    #[Route('/hours/exception/{id}/delete', name: 'app_admin_hours_exception_delete', methods: ['POST'])]
    public function deleteHoursException(
        Request $request,
        HoursException $exception,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$exception->getId(), $request->request->get('_token'))) {
            $entityManager->remove($exception);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'exception d\'horaire a été supprimée avec succès.');
        }
        
        return $this->redirectToRoute('app_admin_hours');
    }

    #[Route('/hero', name: 'app_admin_settings_update_hero', methods: ['POST'])]
    public function updateHeroImage(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageId = $data['imageId'] ?? null;
        
        if (!$imageId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No image ID provided'
            ]);
        }
        
        $image = $imageRepository->find($imageId);
        
        if (!$image) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Image not found'
            ]);
        }
        
        try {
            // Clear any existing hero images
            $existingHeroImages = $imageRepository->findBy(['purpose' => 'hero']);
            foreach ($existingHeroImages as $heroImage) {
                $heroImage->setPurpose(null);
                $this->entityManager->persist($heroImage);
            }
            
            // Set the new hero image
            $image->setPurpose('hero');
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Hero image updated successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating hero image: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/admin/settings/quote-background', name: 'app_admin_settings_update_quote_background', methods: ['POST'])]
    public function updateQuoteBackgroundImage(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageId = $data['imageId'] ?? null;
        
        if (!$imageId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No image ID provided'
            ]);
        }
        
        $image = $imageRepository->find($imageId);
        
        if (!$image) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Image not found'
            ]);
        }
        
        try {
            // Clear any existing quote background images
            $existingImages = $imageRepository->findBy(['purpose' => 'quote_background']);
            foreach ($existingImages as $existingImage) {
                $existingImage->setPurpose(null);
                $this->entityManager->persist($existingImage);
            }
            
            // Set the new quote background image
            $image->setPurpose('quote_background');
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Quote background image updated successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating quote background image: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/admin/settings/reservation-background', name: 'app_admin_settings_update_reservation_background', methods: ['POST'])]
    public function updateReservationBackgroundImage(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        $imageId = $data['imageId'] ?? null;
        
        if (!$imageId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No image ID provided'
            ]);
        }
        
        $image = $imageRepository->find($imageId);
        
        if (!$image) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Image not found'
            ]);
        }
        
        try {
            // Clear any existing reservation background images
            $existingImages = $imageRepository->findBy(['purpose' => 'reservation_background']);
            foreach ($existingImages as $existingImage) {
                $existingImage->setPurpose(null);
                $this->entityManager->persist($existingImage);
            }
            
            // Check for alternative naming and clear those too
            $existingAlternativeImages = $imageRepository->findBy(['purpose' => 'Reservation Background']);
            foreach ($existingAlternativeImages as $existingImage) {
                $existingImage->setPurpose(null);
                $this->entityManager->persist($existingImage);
            }
            
            // Set the new reservation background image
            $image->setPurpose('reservation_background');
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Reservation background image updated successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating reservation background image: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/admin/settings/chef-image', name: 'app_admin_settings_update_chef_image', methods: ['POST'])]
    public function updateChefImage(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        $imageId = $data['imageId'] ?? null;
        
        if (!$imageId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No image ID provided'
            ]);
        }
        
        $image = $imageRepository->find($imageId);
        
        if (!$image) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Image not found'
            ]);
        }
        
        try {
            // Clear any existing chef images
            $existingImages = $imageRepository->findBy(['purpose' => 'chef']);
            foreach ($existingImages as $existingImage) {
                $existingImage->setPurpose(null);
                $this->entityManager->persist($existingImage);
            }
            
            // Set the new chef image
            $image->setPurpose('chef');
            $this->entityManager->persist($image);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Chef image updated successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating chef image: ' . $e->getMessage()
            ]);
        }
    }
}
