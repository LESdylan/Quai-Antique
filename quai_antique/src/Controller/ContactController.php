<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // In a production environment, you would send an actual email
            try {
                $email = (new Email())
                    ->from($data['email'])
                    ->to('contact@quaiantique.fr')
                    ->subject('Contact depuis le site - ' . $data['subject'])
                    ->text($data['message'] . "\n\nDe: " . $data['name'] . "\nEmail: " . $data['email']);
                
                // Uncomment in production
                // $mailer->send($email);
                
                $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
                return $this->redirectToRoute('app_contact');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.');
            }
        }
        
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
