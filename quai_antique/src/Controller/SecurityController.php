<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to appropriate page
        if ($this->getUser()) {
            // If the user is an admin, redirect to admin dashboard
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            // Otherwise redirect to homepage
            return $this->redirectToRoute('app_home');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Add a flash message if there was an error
        if ($error) {
            $this->addFlash('error', 'Identifiants incorrects. Veuillez réessayer.');
        }
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method should not be reached!');
    }
}
