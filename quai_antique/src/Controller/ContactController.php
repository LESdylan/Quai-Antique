<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ContactRepository $contactRepository;

    public function __construct(EntityManagerInterface $entityManager, ContactRepository $contactRepository)
    {
        $this->entityManager = $entityManager;
        $this->contactRepository = $contactRepository;
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set submission date and read status
                $contact->setCreatedAt(new \DateTimeImmutable());
                $contact->setIsRead(false);
                
                // Save to database
                $this->entityManager->persist($contact);
                $this->entityManager->flush();
                
                // Add success flash message
                $this->addFlash('success', 'Votre message a été envoyé avec succès !');
                
                // Redirect to prevent resubmission
                return $this->redirectToRoute('app_contact_success');
            } catch (\Exception $e) {
                // Log the error
                error_log('Contact form error: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message.');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contact/success', name: 'app_contact_success')]
    public function success(): Response
    {
        return $this->render('contact/success.html.twig');
    }
}
