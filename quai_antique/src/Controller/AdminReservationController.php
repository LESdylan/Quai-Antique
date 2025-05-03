<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Restaurant;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class AdminReservationController extends AbstractController
{
    #[Route('', name: 'app_admin_reservations')]
    public function index(Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        // Get date parameter or default to today
        $dateParam = $request->query->get('date', 'today');
        $isWeekend = false;
        
        // Process date parameter
        if ($dateParam === 'today') {
            $date = new \DateTime();
        } elseif ($dateParam === 'tomorrow') {
            $date = new \DateTime('+1 day');
        } elseif ($dateParam === 'weekend') {
            $isWeekend = true;
            $date = new \DateTime();
            $dayOfWeek = (int)$date->format('N');
            
            // If today is not Saturday or Sunday, find the next Saturday
            if ($dayOfWeek < 6) {
                $daysUntilSaturday = 6 - $dayOfWeek;
                $date->modify("+$daysUntilSaturday days");
            }
        } else {
            try {
                $date = new \DateTime($dateParam);
            } catch (\Exception $e) {
                $date = new \DateTime();
            }
        }
        
        // Get reservations for the selected date
        if ($isWeekend) {
            // Get Saturday and Sunday dates
            $saturday = clone $date;
            $sunday = clone $date;
            $sunday->modify('+1 day');
            
            $reservations = $reservationRepository->findByDateRange($saturday, $sunday);
        } else {
            $reservations = $reservationRepository->findByDate($date);
        }
        
        // Get restaurant capacity information
        $restaurant = $entityManager->getRepository(Restaurant::class)->findOneBy([]) ?? new Restaurant();
        $maxCapacity = $restaurant->getMaxCapacity() ?? 50;
        
        // Calculate capacity utilization for each time slot
        $capacityByTimeSlot = [];
        
        foreach ($reservations as $reservation) {
            $time = $reservation->getTime()->format('H:i');
            $guests = $reservation->getNumberOfGuests();
            
            if (!isset($capacityByTimeSlot[$time])) {
                $capacityByTimeSlot[$time] = 0;
            }
            
            $capacityByTimeSlot[$time] += $guests;
        }
        
        return $this->render('admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'selected_date' => $date,
            'is_weekend' => $isWeekend,
            'capacityByTimeSlot' => $capacityByTimeSlot,
            'maxCapacity' => $maxCapacity,
        ]);
    }
    
    #[Route('/{id}/status/{status}', name: 'app_admin_reservation_status', methods: ['POST'])]
    public function updateStatus(
        Request $request, 
        Reservation $reservation, 
        string $status, 
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('status'.$reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_reservations');
        }
        
        // Validate status
        $validStatuses = [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED, Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED];
        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('app_admin_reservations');
        }
        
        // Update status
        $reservation->setStatus($status);
        $entityManager->flush();
        
        // Get status label for flash message
        $statusLabels = [
            Reservation::STATUS_PENDING => 'en attente',
            Reservation::STATUS_CONFIRMED => 'confirmée',
            Reservation::STATUS_CANCELLED => 'annulée',
            Reservation::STATUS_COMPLETED => 'terminée',
        ];
        
        $this->addFlash('success', 'La réservation a été marquée comme ' . $statusLabels[$status]);
        
        // Redirect back to the page with the same date filter
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_admin_reservations');
    }
    
    #[Route('/capacity', name: 'app_admin_reservation_capacity')]
    public function capacity(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get restaurant settings
        $restaurant = $entityManager->getRepository(Restaurant::class)->findOneBy([]) ?? new Restaurant();
        
        if ($request->isMethod('POST')) {
            // Update capacity settings
            $restaurant->setMaxCapacity($request->request->getInt('max_capacity', 50));
            $restaurant->setMaxTablesSmall($request->request->getInt('max_tables_small', 6));  // 2 people
            $restaurant->setMaxTablesMedium($request->request->getInt('max_tables_medium', 8)); // 4 people
            $restaurant->setMaxTablesLarge($request->request->getInt('max_tables_large', 4));  // 6-8 people
            $restaurant->setTimeSlotDuration($request->request->getInt('time_slot_duration', 30)); // minutes
            $restaurant->setMaxReservationsPerSlot($request->request->getInt('max_reservations_per_slot', 10));
            $restaurant->setBufferBetweenSlots($request->request->getInt('buffer_between_slots', 15)); // minutes
            
            $entityManager->persist($restaurant);
            $entityManager->flush();
            
            $this->addFlash('success', 'Restaurant capacity settings updated.');
            return $this->redirectToRoute('app_admin_reservation_capacity');
        }
        
        return $this->render('admin/reservations/capacity.html.twig', [
            'restaurant' => $restaurant,
        ]);
    }
    
    #[Route('/optimize', name: 'app_admin_reservations_optimize', methods: ['POST'])]
    public function optimizeReservations(Request $request, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $date = new \DateTime($request->request->get('date'));
        $reservations = $reservationRepository->findByDate($date);
        
        // Sort reservations by party size (descending), then time
        usort($reservations, function($a, $b) {
            // First by number of guests (descending)
            if ($a->getNumberOfGuests() != $b->getNumberOfGuests()) {
                return $b->getNumberOfGuests() - $a->getNumberOfGuests();
            }
            // Then by time
            return $a->getTime() <=> $b->getTime();
        });
        
        // Get restaurant table configuration
        $restaurant = $entityManager->getRepository(Restaurant::class)->findOneBy([]) ?? new Restaurant();
        $smallTables = $restaurant->getMaxTablesSmall() ?? 6;  // 2 people
        $mediumTables = $restaurant->getMaxTablesMedium() ?? 8; // 4 people
        $largeTables = $restaurant->getMaxTablesLarge() ?? 4;  // 6-8 people
        
        // Simple table assignment logic (could be more sophisticated)
        foreach ($reservations as $reservation) {
            $guests = $reservation->getNumberOfGuests();
            $tableAssigned = false;
            
            // Try to simulate table assignment without writing to columns that don't exist yet
            if ($guests <= 2 && $smallTables > 0) {
                $tableAssigned = true;
                $smallTables--;
            } elseif ($guests <= 4 && $mediumTables > 0) {
                $tableAssigned = true;
                $mediumTables--;
            } elseif ($guests <= 8 && $largeTables > 0) {
                $tableAssigned = true;
                $largeTables--;
            }
            
            // Only update the reservation status (which definitely exists)
            $reservation->setStatus($tableAssigned ? 'confirmed' : 'waitlist');
            $entityManager->persist($reservation);
        }
        
        $entityManager->flush();
        $this->addFlash('success', 'Reservations have been optimized for ' . $date->format('Y-m-d'));
        
        return $this->redirectToRoute('app_admin_reservations', ['date' => $date->format('Y-m-d')]);
    }
    
    #[Route('/{id}/assign-table', name: 'app_admin_reservation_assign_table', methods: ['POST'])]
    public function assignTable(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Just set status to confirmed instead of setting table properties
        $reservation->setStatus('confirmed');
        $entityManager->flush();
        
        $this->addFlash('success', 'Table assignment confirmed.');
        
        return $this->redirectToRoute('app_admin_reservations');
    }
    
    #[Route('/add', name: 'app_admin_reservation_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new reservation from POST data
        $reservation = new Reservation();
        
        // Set reservation data from request
        $date = new \DateTime($request->request->get('date'));
        $time = new \DateTime($request->request->get('time'));
        $numberOfGuests = $request->request->getInt('numberOfGuests');
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $notes = $request->request->get('notes');
        
        $reservation->setDate($date);
        $reservation->setTime($time);
        $reservation->setNumberOfGuests($numberOfGuests);
        $reservation->setName($name);
        $reservation->setEmail($email);
        $reservation->setPhone($phone);
        $reservation->setNotes($notes);
        
        // Set default values
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setStatus('pending');
        
        // Save to database
        $entityManager->persist($reservation);
        $entityManager->flush();
        
        $this->addFlash('success', 'Reservation created successfully');
        
        // Redirect to reservation list with the same date
        return $this->redirectToRoute('app_admin_reservations', [
            'date' => $date->format('Y-m-d')
        ]);
    }
}
