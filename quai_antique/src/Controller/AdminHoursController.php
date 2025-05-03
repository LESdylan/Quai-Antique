<?php

namespace App\Controller;

use App\Entity\Hours;
use App\Entity\HoursException;
use App\Repository\HoursRepository;
use App\Repository\HoursExceptionRepository;
use App\Service\SchemaToolHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hours')]
#[IsGranted('ROLE_ADMIN')]
class AdminHoursController extends AbstractController
{
    #[Route('/', name: 'app_admin_hours')]
    public function index(HoursRepository $hoursRepository, HoursExceptionRepository $hoursExceptionRepository, SchemaToolHelper $schemaToolHelper): Response
    {
        // Ensure the hours_exception table exists
        try {
            if (!$schemaToolHelper->hoursExceptionTableExists()) {
                $schemaToolHelper->fixHoursExceptionTable();
            }
            
            // Get regular hours
            $openingHours = [];
            $hours = $hoursRepository->findAllOrdered();
            
            // Initialize with default values if not set
            for ($i = 1; $i <= 7; $i++) {
                $openingHours[$i] = [
                    'lunch_start' => '12:00',
                    'lunch_end' => '14:00',
                    'dinner_start' => '19:00',
                    'dinner_end' => '22:00',
                    'is_closed' => false,
                ];
            }
            
            // Override with actual values from database
            foreach ($hours as $hour) {
                $dayOfWeek = $hour->getDayOfWeek();
                
                $openingHours[$dayOfWeek] = [
                    'lunch_start' => $hour->getLunchOpeningTime() ? $hour->getLunchOpeningTime()->format('H:i') : null,
                    'lunch_end' => $hour->getLunchClosingTime() ? $hour->getLunchClosingTime()->format('H:i') : null,
                    'dinner_start' => $hour->getDinnerOpeningTime() ? $hour->getDinnerOpeningTime()->format('H:i') : null,
                    'dinner_end' => $hour->getDinnerClosingTime() ? $hour->getDinnerClosingTime()->format('H:i') : null,
                    'is_closed' => $hour->isIsClosed(),
                ];
            }
            
            // Get exceptions
            $exceptions = $hoursExceptionRepository->findBy([], ['date' => 'ASC']);
            
            // Get display settings
            $displaySettings = $this->getDisplaySettings();
            
            return $this->render('admin/hours/index.html.twig', [
                'openingHours' => $openingHours,
                'exceptions' => $exceptions,
                'displaySettings' => $displaySettings,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Database error: ' . $e->getMessage());
            return $this->render('admin/hours/index.html.twig', [
                'openingHours' => [],
                'exceptions' => [],
                'displaySettings' => $this->getDisplaySettings(),
                'error' => $e->getMessage()
            ]);
        }   
    }
    
    #[Route('/update', name: 'app_admin_hours_update', methods: ['POST'])]
    public function update(Request $request, HoursRepository $hoursRepository, EntityManagerInterface $entityManager): Response
    {
        // Fix 2: Get hours as array without default value (removing the array as second argument)
        $hoursData = $request->request->get('hours');
        
        if (!is_array($hoursData)) {
            $this->addFlash('error', 'Invalid hours data provided');
            return $this->redirectToRoute('app_admin_hours');
        }
        
        // Process each day's hours
        foreach ($hoursData as $dayOfWeek => $dayData) {
            // Convert to 1-based index (Monday = 1, Sunday = 7)
            $dayIndex = $dayOfWeek + 1;
            
            // Find or create hours entity
            $hours = $hoursRepository->findOneBy(['dayOfWeek' => $dayIndex]);
            if (!$hours) {
                $hours = new Hours();
                $hours->setDayOfWeek($dayIndex);
            }
            
            // Fix 3: Ensure dayData is an array and use it safely
            if (!is_array($dayData)) {
                continue; // Skip this day if data is invalid
            }
            
            // Check if day is closed
            $isClosed = isset($dayData['is_closed']);
            $hours->setIsClosed($isClosed);
            
            if (!$isClosed) {
                // Set lunch hours
                if (!empty($dayData['lunch_start']) && !empty($dayData['lunch_end'])) {
                    $hours->setLunchOpeningTime(new \DateTime($dayData['lunch_start']));
                    $hours->setLunchClosingTime(new \DateTime($dayData['lunch_end']));
                } else {
                    $hours->setLunchOpeningTime(null);
                    $hours->setLunchClosingTime(null);
                }
                
                // Set dinner hours
                if (!empty($dayData['dinner_start']) && !empty($dayData['dinner_end'])) {
                    $hours->setDinnerOpeningTime(new \DateTime($dayData['dinner_start']));
                    $hours->setDinnerClosingTime(new \DateTime($dayData['dinner_end']));
                } else {
                    $hours->setDinnerOpeningTime(null);
                    $hours->setDinnerClosingTime(null);
                }
            } else {
                // If closed, clear all times
                $hours->setLunchOpeningTime(null);
                $hours->setLunchClosingTime(null);
                $hours->setDinnerOpeningTime(null);
                $hours->setDinnerClosingTime(null);
            }
            
            // Save to database
            $entityManager->persist($hours);
        }
        
        $entityManager->flush();
        $this->addFlash('success', 'Les horaires ont été mis à jour avec succès.');
        
        return $this->redirectToRoute('app_admin_hours');
    }
    
    #[Route('/exceptions/add', name: 'app_admin_hours_exception_add', methods: ['POST'])]
    public function addException(Request $request, EntityManagerInterface $entityManager): Response
    {
        $date = new \DateTime($request->request->get('date'));
        $description = $request->request->get('description');
        $isClosed = $request->request->get('is_closed', false) === '1';
        $openingTime = $request->request->get('opening_time');
        $closingTime = $request->request->get('closing_time');
        
        // Create exception entity
        $exception = new HoursException();
        $exception->setDate($date);
        $exception->setDescription($description);
        $exception->setIsClosed($isClosed);
        
        if (!$isClosed) {
            $exception->setOpeningTime($openingTime);
            $exception->setClosingTime($closingTime);
        }
        
        $entityManager->persist($exception);
        $entityManager->flush();
        
        $this->addFlash('success', 'L\'exception a été ajoutée avec succès.');
        return $this->redirectToRoute('app_admin_hours');
    }
    
    #[Route('/exceptions/{id}/delete', name: 'app_admin_hours_exception_delete', methods: ['POST'])]
    public function deleteException(HoursException $exception, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exception->getId(), $request->request->get('_token'))) {
            $entityManager->remove($exception);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'exception a été supprimée.');
        }
        
        return $this->redirectToRoute('app_admin_hours');
    }
    
    #[Route('/display-settings', name: 'app_admin_hours_display_settings', methods: ['POST'])]
    public function updateDisplaySettings(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('hours_display_settings', $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }
        
        try {
            // In a real application, you'd save this to the database
            // For now, we'll just simulate success
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function getDisplaySettings(): array
    {
        // In a real application, you'd fetch this from the database
        // For now, return default values
        return [
            'homepage' => true,
            'footer' => true,
            'reservation' => true,
        ];
    }
}
