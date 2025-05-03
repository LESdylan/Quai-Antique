<?php

namespace App\Controller;

use App\Entity\Page;
use App\Form\PageType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/pages')]
#[IsGranted('ROLE_ADMIN')]
class AdminPageController extends AbstractController
{
    private $slugger;
    
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    
    #[Route('/', name: 'app_admin_pages')]
    public function index(PageRepository $pageRepository): Response
    {
        $pages = $pageRepository->findAll();
        
        return $this->render('admin/pages/index.html.twig', [
            'pages' => $pages,
        ]);
    }
    
    #[Route('/new', name: 'app_admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = new Page();
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Generate slug from title
            $slug = strtolower($this->slugger->slug($page->getTitle()));
            $page->setSlug($slug);
            
            $entityManager->persist($page);
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été créée avec succès.');
            return $this->redirectToRoute('app_admin_pages');
        }
        
        return $this->render('admin/pages/new.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_admin_page_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Page $page, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Update the slug if title changed
            $slug = strtolower($this->slugger->slug($page->getTitle()));
            $page->setSlug($slug);
            
            $page->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été mise à jour.');
            return $this->redirectToRoute('app_admin_pages');
        }
        
        return $this->render('admin/pages/edit.html.twig', [
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_page_delete', methods: ['POST'])]
    public function delete(Request $request, Page $page, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$page->getId(), $request->request->get('_token'))) {
            $entityManager->remove($page);
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été supprimée.');
        }
        
        return $this->redirectToRoute('app_admin_pages');
    }
    
    #[Route('/{id}/toggle', name: 'app_admin_page_toggle', methods: ['POST'])]
    public function toggle(Page $page, EntityManagerInterface $entityManager): Response
    {
        $page->setIsPublished(!$page->isIsPublished());
        $entityManager->flush();
        
        $status = $page->isIsPublished() ? 'publiée' : 'dépubliée';
        $this->addFlash('success', "La page a été $status.");
        
        return $this->redirectToRoute('app_admin_pages');
    }
}
