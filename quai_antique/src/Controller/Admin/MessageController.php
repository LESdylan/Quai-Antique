<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/messages')]
class MessageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;
    
    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
    }

    #[Route('/', name: 'admin_message_index')]
    public function index(Request $request): Response
    {
        // Get filter parameters
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search');
        
        // Get messages based on filters
        $messages = $this->messageRepository->findByFilters($status, $search);
        
        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages,
            'unreadCount' => $this->messageRepository->countUnreadMessages(),
            'currentStatus' => $status,
            'currentSearch' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_message_show', methods: ['GET'])]
    public function show(Message $message): Response
    {
        // Mark message as read if it's not already read
        if (!$message->isIsRead()) {
            $message->markAsRead();
            $this->entityManager->flush();
        }
        
        return $this->render('admin/message/show.html.twig', [
            'message' => $message,
        ]);
    }
    
    #[Route('/{id}/mark-as-read', name: 'admin_message_mark_read', methods: ['POST'])]
    public function markAsRead(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('mark_as_read'.$message->getId(), $request->request->get('_token'))) {
            $message->markAsRead();
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été marqué comme lu.');
        }

        return $this->redirectToRoute('admin_message_index');
    }
    
    #[Route('/{id}/mark-as-replied', name: 'admin_message_mark_replied', methods: ['POST'])]
    public function markAsReplied(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('mark_as_replied'.$message->getId(), $request->request->get('_token'))) {
            $message->markAsReplied();
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été marqué comme répondu.');
        }

        return $this->redirectToRoute('admin_message_index');
    }
    
    #[Route('/{id}/archive', name: 'admin_message_archive', methods: ['POST'])]
    public function archive(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('archive'.$message->getId(), $request->request->get('_token'))) {
            $message->archive();
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été archivé.');
        }

        return $this->redirectToRoute('admin_message_index');
    }
    
    #[Route('/{id}/delete', name: 'admin_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message): Response
    {
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($message);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été supprimé.');
        }

        return $this->redirectToRoute('admin_message_index');
    }
}
