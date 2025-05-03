<?php

namespace App\Controller;

use App\Entity\Reservation;
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
    public function index(Request $request, ReservationRepository $reservationRepository): Response
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
        
        return $this->render('admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'selected_date' => $date,
            'is_weekend' => $isWeekend,
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
}
