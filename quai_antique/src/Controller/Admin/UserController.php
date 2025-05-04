<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'admin_user_index')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Implementation for user creation form
        return $this->render('admin/user/new.html.twig');
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        // Implementation for user editing form
        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Prevent self-deletion
            if ($user->getId() === $this->getUser()->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('admin_user_index');
            }
            
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
    
    #[Route('/profile', name: 'admin_user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();
        
        // Handle form submission for profile updates
        if ($request->isMethod('POST')) {
            // Update user data processing would go here
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
        }
        
        return $this->render('admin/user/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
