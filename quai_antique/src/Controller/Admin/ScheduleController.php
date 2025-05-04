<?php

namespace App\Controller\Admin;

use App\Entity\Schedule;
use App\Form\ScheduleType;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/schedule')]
class ScheduleController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ScheduleRepository $scheduleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ScheduleRepository $scheduleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->scheduleRepository = $scheduleRepository;
    }

    #[Route('/', name: 'admin_schedule_index', methods: ['GET'])]
    public function index(): Response
    {
        $schedules = $this->scheduleRepository->findBy([], ['dayNumber' => 'ASC']);
        
        return $this->render('admin/schedule/index.html.twig', [
            'schedules' => $schedules,
        ]);
    }

    #[Route('/new', name: 'admin_schedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $schedule = new Schedule();
        $form = $this->createForm(ScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set day_of_week same as dayName if not already set
            if (!$schedule->getDayOfWeek()) {
                $schedule->setDayOfWeek($schedule->getDayName());
            }
            
            // If the restaurant is closed on this day, clear the opening hours
            if ($schedule->isIsClosed()) {
                $schedule->setLunchOpeningTime(null);
                $schedule->setLunchClosingTime(null);
                $schedule->setDinnerOpeningTime(null);
                $schedule->setDinnerClosingTime(null);
            }
            
            $this->entityManager->persist($schedule);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Horaire créé avec succès.');
            return $this->redirectToRoute('admin_schedule_index');
        }

        return $this->render('admin/schedule/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_schedule_show', methods: ['GET'])]
    public function show(Schedule $schedule): Response
    {
        return $this->render('admin/schedule/show.html.twig', [
            'schedule' => $schedule,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Schedule $schedule): Response
    {
        $form = $this->createForm(ScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set day_of_week same as dayName if not already set
            if (!$schedule->getDayOfWeek()) {
                $schedule->setDayOfWeek($schedule->getDayName());
            }
            
            // If the restaurant is closed on this day, clear the opening hours
            if ($schedule->isIsClosed()) {
                $schedule->setLunchOpeningTime(null);
                $schedule->setLunchClosingTime(null);
                $schedule->setDinnerOpeningTime(null);
                $schedule->setDinnerClosingTime(null);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Horaire modifié avec succès.');
            return $this->redirectToRoute('admin_schedule_index');
        }

        return $this->render('admin/schedule/edit.html.twig', [
            'schedule' => $schedule,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_schedule_toggle', methods: ['POST'])]
    public function toggle(Request $request, Schedule $schedule): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$schedule->getId(), $request->request->get('_token'))) {
            $schedule->setIsActive(!$schedule->isIsActive());
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin_schedule_index');
    }

    #[Route('/{id}/delete', name: 'admin_schedule_delete', methods: ['POST'])]
    public function delete(Request $request, Schedule $schedule): Response
    {
        if ($this->isCsrfTokenValid('delete'.$schedule->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($schedule);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Horaire supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_schedule_index');
    }
}
