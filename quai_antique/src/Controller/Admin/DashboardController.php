<?php

namespace App\Controller\Admin;

use App\Repository\MessageRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\MenuItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack) 
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(
        MessageRepository $messageRepository,
        ReservationRepository $reservationRepository = null,
        UserRepository $userRepository = null,
        MenuItemRepository $menuItemRepository = null
    ): Response
    {
        // Get message data
        $unreadMessages = $messageRepository->countUnreadMessages();
        $totalMessages = count($messageRepository->findAll());
        $recentMessages = $messageRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        $data = [
            'unreadMessages' => $unreadMessages,
            'totalMessages' => $totalMessages,
            'recentMessages' => $recentMessages
        ];
        
        // Update session with unread message count - using the injected RequestStack
        $this->requestStack->getSession()->set('unread_messages', $unreadMessages);
        
        // Add reservation stats if repository exists
        if ($reservationRepository !== null) {
            $data['upcomingReservations'] = $reservationRepository->findUpcomingReservations(5);
            $data['todayReservations'] = $reservationRepository->countTodayReservations();
        }
        
        // Add user stats if repository exists
        if ($userRepository !== null) {
            $data['totalUsers'] = count($userRepository->findAll());
        }
        
        // Add menu stats if repository exists
        if ($menuItemRepository !== null) {
            $data['totalMenuItems'] = count($menuItemRepository->findAll());
        } else {
            $data['totalMenuItems'] = 0;
        }
        
        return $this->render('admin/dashboard/index.html.twig', $data);
    }
}
