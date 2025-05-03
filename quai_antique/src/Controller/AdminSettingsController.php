<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\HoursException;
use App\Form\RestaurantSettingsType;
use App\Form\HoursExceptionType;
use App\Repository\HoursRepository;
use App\Repository\RestaurantRepository;
use App\Repository\HoursExceptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
        SluggerInterface $slugger
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
        
        return $this->render('admin/settings/index.html.twig', [
            'restaurant_form' => $form->createView(),
            'restaurant' => $restaurant,
            'hours' => $hours,
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
}
