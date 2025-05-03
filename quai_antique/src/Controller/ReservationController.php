<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Restaurant;
use App\Form\ReservationType;
use App\Repository\HoursRepository;
use App\Repository\ImageRepository;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(
        Request $request, 
        EntityManagerInterface $entityManager, 
        HoursRepository $hoursRepository, 
        ImageRepository $imageRepository,
        RestaurantRepository $restaurantRepository
    ): Response {
        // Create a new reservation form
        $form = $this->createForm(ReservationType::class);
        $form->handleRequest($request);
        
        // Get restaurant opening hours
        $hours = $hoursRepository->findAllOrdered();
        
        // Get reservation background image
        $reservationImage = $imageRepository->findOneBy(['purpose' => 'reservation_background', 'isActive' => true]);
        
        // If not found, try alternative naming
        if (!$reservationImage) {
            $reservationImage = $imageRepository->findOneBy(['purpose' => 'Reservation Background', 'isActive' => true]);
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $form->getData();
            
            // Check if reservation time slot is at capacity
            $date = $reservation->getDate();
            $time = $reservation->getTime();
            $guests = $reservation->getNumberOfGuests();
            
            // Get restaurant capacity settings
            $restaurant = $restaurantRepository->findOneBy([]);
            $maxCapacity = $restaurant && method_exists($restaurant, 'getMaxCapacity') ? $restaurant->getMaxCapacity() : 50;
            
            // Get existing reservations for this date/time
            $existingReservations = $entityManager->getRepository(Reservation::class)
                ->findByDateTime($date, $time);
            
            // Calculate current capacity
            $currentCapacity = 0;
            foreach ($existingReservations as $existingReservation) {
                $currentCapacity += $existingReservation->getNumberOfGuests();
            }
            
            // Check if adding this reservation would exceed capacity
            if (($currentCapacity + $guests) > $maxCapacity) {
                $this->addFlash('error', 'Désolé, nous sommes complets à cette heure. Veuillez choisir un autre horaire.');
                return $this->render('reservation/index.html.twig', [
                    'form' => $form->createView(),
                    'hours' => $hours,
                    'reservation_image' => $reservationImage,
                ]);
            }
            
            // Process form submission (redirect to login if not authenticated)
            if (!$this->getUser()) {
                $this->addFlash('info', 'Veuillez vous connecter pour finaliser votre réservation.');
                return $this->redirectToRoute('app_login', [
                    'reservation' => true
                ]);
            }
            
            $reservation->setUser($this->getUser());
            $reservation->setCreatedAt(new \DateTime());
            $reservation->setStatus('pending');
            
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');
            return $this->redirectToRoute('app_reservation_confirmation', [
                'id' => $reservation->getId()
            ]);
        }
        
        return $this->render('reservation/index.html.twig', [
            'form' => $form->createView(),
            'hours' => $hours,
            'reservation_image' => $reservationImage,
            'controller_name' => 'ReservationController',
        ]);
    }
    
    #[Route('/reservation/available-slots', name: 'app_reservation_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request, HoursRepository $hoursRepository, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): JsonResponse
    {
        $date = $request->query->get('date');
        
        if (!$date) {
            return new JsonResponse(['error' => 'Date parameter is required'], 400);
        }
        
        try {
            $dateObj = new \DateTime($date);
            $dayOfWeek = (int)$dateObj->format('N');
            
            // Get hours for this day
            $dayHours = $hoursRepository->findOneBy(['dayOfWeek' => $dayOfWeek]);
            
            if (!$dayHours || $dayHours->isIsClosed()) {
                return new JsonResponse([
                    'available' => false,
                    'message' => 'Le restaurant est fermé ce jour-là.'
                ]);
            }
            
            // Get restaurant capacity settings
            $restaurant = $entityManager->getRepository(Restaurant::class)->findOneBy([]) ?? new Restaurant();
            $maxCapacity = $restaurant->getMaxCapacity() ?? 50; // Default to 50 if not set
            
            // Get existing reservations for this date
            $reservations = $reservationRepository->findByDate($dateObj);
            
            // Calculate capacity for each time slot
            $capacityByTimeSlot = [];
            foreach ($reservations as $reservation) {
                $timeKey = $reservation->getTime()->format('H:i');
                if (!isset($capacityByTimeSlot[$timeKey])) {
                    $capacityByTimeSlot[$timeKey] = 0;
                }
                $capacityByTimeSlot[$timeKey] += $reservation->getNumberOfGuests();
            }
            
            // Generate time slots
            $timeSlots = [];
            
            // Lunch slots
            if ($dayHours->getLunchOpeningTime() && $dayHours->getLunchClosingTime()) {
                $start = clone $dayHours->getLunchOpeningTime();
                $end = clone $dayHours->getLunchClosingTime();
                
                $currentSlot = $start;
                while ($currentSlot < $end) {
                    $timeKey = $currentSlot->format('H:i');
                    $currentCapacity = $capacityByTimeSlot[$timeKey] ?? 0;
                    $availableCapacity = max(0, $maxCapacity - $currentCapacity);
                    $capacityPercentage = $maxCapacity > 0 ? ($currentCapacity / $maxCapacity) * 100 : 0;
                    
                    $timeSlots[] = [
                        'time' => $timeKey,
                        'period' => 'lunch',
                        'available' => $availableCapacity > 0,
                        'remainingCapacity' => $availableCapacity,
                        'capacityPercentage' => $capacityPercentage
                    ];
                    
                    $currentSlot = (clone $currentSlot)->modify('+15 minutes');
                }
            }
            
            // Dinner slots
            if ($dayHours->getDinnerOpeningTime() && $dayHours->getDinnerClosingTime()) {
                $start = clone $dayHours->getDinnerOpeningTime();
                $end = clone $dayHours->getDinnerClosingTime();
                
                $currentSlot = $start;
                while ($currentSlot < $end) {
                    $timeKey = $currentSlot->format('H:i');
                    $currentCapacity = $capacityByTimeSlot[$timeKey] ?? 0;
                    $availableCapacity = max(0, $maxCapacity - $currentCapacity);
                    $capacityPercentage = $maxCapacity > 0 ? ($currentCapacity / $maxCapacity) * 100 : 0;
                    
                    $timeSlots[] = [
                        'time' => $timeKey,
                        'period' => 'dinner',
                        'available' => $availableCapacity > 0,
                        'remainingCapacity' => $availableCapacity,
                        'capacityPercentage' => $capacityPercentage
                    ];
                    
                    $currentSlot = (clone $currentSlot)->modify('+15 minutes');
                }
            }
            
            return new JsonResponse([
                'available' => true,
                'timeSlots' => $timeSlots,
                'maxCapacity' => $maxCapacity
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    #[Route('/reservation/confirmation/{id}', name: 'app_reservation_confirmation')]
    public function confirmation(Reservation $reservation): Response
    {
        // Security check
        if ($this->getUser() !== $reservation->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        
        return $this->render('reservation/confirmation.html.twig', [
            'reservation' => $reservation
        ]);
    }
    
    #[IsGranted('ROLE_USER')]
    #[Route('/mes-reservations', name: 'app_my_reservations')]
    public function myReservations(EntityManagerInterface $entityManager): Response
    {
        $reservations = $entityManager->getRepository(Reservation::class)
            ->findBy(['user' => $this->getUser()], ['date' => 'DESC']);
        
        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => $reservations
        ]);
    }
    
    #[IsGranted('ROLE_USER')]
    #[Route('/reservation/cancel/{id}', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancelReservation(Reservation $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Security check
        if ($this->getUser() !== $reservation->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus('cancelled');
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        }
        
        return $this->redirectToRoute('app_my_reservations');
    }
}
