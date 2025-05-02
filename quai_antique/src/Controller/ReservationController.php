<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\HoursRepository;
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
    public function index(Request $request, EntityManagerInterface $entityManager, HoursRepository $hoursRepository): Response
    {
        // Create a new reservation form
        $form = $this->createForm(ReservationType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Process form submission (redirect to login if not authenticated)
            if (!$this->getUser()) {
                $this->addFlash('info', 'Veuillez vous connecter pour finaliser votre réservation.');
                return $this->redirectToRoute('app_login', [
                    'reservation' => true
                ]);
            }
            
            $reservation = $form->getData();
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
        
        // Get restaurant opening hours
        $hours = $hoursRepository->findAllOrdered();
        
        return $this->render('reservation/index.html.twig', [
            'form' => $form->createView(),
            'hours' => $hours
        ]);
    }
    
    #[Route('/reservation/available-slots', name: 'app_reservation_slots', methods: ['GET'])]
    public function getAvailableSlots(Request $request, HoursRepository $hoursRepository): JsonResponse
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
            
            // Generate time slots in 15-minute increments
            $timeSlots = [];
            
            // Lunch slots
            if ($dayHours->getLunchOpeningTime() && $dayHours->getLunchClosingTime()) {
                $start = clone $dayHours->getLunchOpeningTime();
                $end = clone $dayHours->getLunchClosingTime();
                
                $currentSlot = $start;
                while ($currentSlot < $end) {
                    $timeSlots[] = [
                        'time' => $currentSlot->format('H:i'),
                        'period' => 'lunch'
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
                    $timeSlots[] = [
                        'time' => $currentSlot->format('H:i'),
                        'period' => 'dinner'
                    ];
                    $currentSlot = (clone $currentSlot)->modify('+15 minutes');
                }
            }
            
            return new JsonResponse([
                'available' => true,
                'timeSlots' => $timeSlots
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
