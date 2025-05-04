<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservation')]
class ReservationController extends AbstractController
{
    private $entityManager;
    private $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route('/', name: 'admin_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        // Get filter parameters from request
        $filter = $request->query->get('filter', '');
        $status = $request->query->get('status', '');
        $date = $request->query->get('date');
        
        // Set current date for filtering and display
        $currentDate = $date ? new \DateTime($date) : new \DateTime();
        
        // Build filters array
        $filters = [
            'search' => $filter,
            'status' => $status,
            'date' => $date ? new \DateTime($date) : null,
        ];
        
        // Get reservations with filters
        $reservations = $reservationRepository->findByFilters($filters);
        
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'filter' => $filter,
            'status' => $status,
            'currentDate' => $currentDate, // Add the currentDate variable
        ]);
    }

    #[Route('/new', name: 'admin_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Implement your reservation creation logic
        // This is just a placeholder for the template
        return $this->render('admin/reservation/new.html.twig');
    }

    #[Route('/{id}', name: 'admin_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation): Response
    {
        // Implement your reservation editing logic
        // This is just a placeholder for the template
        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/confirm', name: 'admin_reservation_confirm', methods: ['POST'])]
    public function confirm(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('confirm'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus('confirmed');
            $this->entityManager->flush();
            $this->addFlash('success', 'Réservation confirmée avec succès.');
        }

        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/{id}/cancel', name: 'admin_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus('cancelled');
            $this->entityManager->flush();
            $this->addFlash('success', 'Réservation annulée avec succès.');
        }

        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_reservation_index');
    }
}
