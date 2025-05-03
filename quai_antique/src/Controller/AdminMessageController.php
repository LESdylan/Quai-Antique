<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class AdminMessageController extends AbstractController
{
    #[Route('/', name: 'app_admin_messages')]
    public function index(MessageRepository $messageRepository): Response
    {
        // Get all messages
        $messages = $messageRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'unreadCount' => $messageRepository->countUnread(),
        ]);
    }
    
    #[Route('/unread', name: 'app_admin_messages_unread')]
    public function unread(MessageRepository $messageRepository): Response
    {
        // Get unread messages
        $messages = $messageRepository->findUnread();
        
        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'unreadCount' => count($messages),
            'unreadOnly' => true,
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_message_show')]
    public function show(Message $message, EntityManagerInterface $entityManager): Response
    {
        // Mark as read if not already
        if (!$message->isIsRead()) {
            $message->setIsRead(true);
            $entityManager->flush();
        }
        
        return $this->render('admin/messages/show.html.twig', [
            'message' => $message,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le message a été supprimé.');
        }
        
        return $this->redirectToRoute('app_admin_messages');
    }
    
    #[Route('/{id}/toggle-read', name: 'app_admin_message_toggle_read', methods: ['POST'])]
    public function toggleRead(Message $message, EntityManagerInterface $entityManager): Response
    {
        $message->setIsRead(!$message->isIsRead());
        $entityManager->flush();
        
        return $this->redirectToRoute('app_admin_messages');
    }

    #[Route('/delete-read', name: 'app_admin_messages_delete_read', methods: ['POST'])]
    public function deleteAllRead(Request $request, MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_read_messages', $request->request->get('_token'))) {
            $readMessages = $messageRepository->findBy(['isRead' => true]);
            
            foreach ($readMessages as $message) {
                $entityManager->remove($message);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', count($readMessages) . ' messages lus ont été supprimés.');
        }
        
        return $this->redirectToRoute('app_admin_messages');
    }
}
