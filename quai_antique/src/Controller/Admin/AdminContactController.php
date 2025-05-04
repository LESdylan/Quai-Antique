<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contact', name: 'admin_contact_')]
class AdminContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ContactRepository $contactRepository;

    public function __construct(EntityManagerInterface $entityManager, ContactRepository $contactRepository)
    {
        $this->entityManager = $entityManager;
        $this->contactRepository = $contactRepository;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $messages = $this->contactRepository->findAllOrderedByDate();

        return $this->render('admin/contact/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        // Mark as read when viewed
        if (!$contact->isIsRead()) {
            $contact->setIsRead(true);
            $this->entityManager->flush();
        }

        return $this->render('admin/contact/show.html.twig', [
            'message' => $contact,
        ]);
    }

    #[Route('/{id}/mark-read', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(Request $request, Contact $contact): Response
    {
        if ($this->isCsrfTokenValid('mark-read'.$contact->getId(), $request->request->get('_token'))) {
            $contact->setIsRead(true);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été marqué comme lu.');
        }

        return $this->redirectToRoute('admin_contact_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($contact);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le message a été supprimé.');
        }

        return $this->redirectToRoute('admin_contact_index');
    }
}
