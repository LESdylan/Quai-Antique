<?php

namespace App\Controller\Admin;

use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MenuRepository $menuRepository;
    private CategoryRepository $categoryRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MenuRepository $menuRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->entityManager = $entityManager;
        $this->menuRepository = $menuRepository;
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('/', name: 'admin_menu_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Get filter parameters
        $search = $request->query->get('search', '');
        $categoryId = $request->query->get('category');
        
        // Build filter criteria
        $criteria = [];
        if ($search) {
            $criteria['search'] = $search;
        }
        if ($categoryId) {
            $criteria['category'] = $categoryId;
        }
        
        // Get menus with filters
        $menus = $this->menuRepository->findByFilters($criteria);
        
        // Get all categories for the filter dropdown
        $categories = $this->categoryRepository->findAll();
        
        return $this->render('admin/menu/index.html.twig', [
            'menus' => $menus,
            'search' => $search,
            'category' => $categoryId,
            'categories' => $categories, // Pass categories to the template
        ]);
    }

    #[Route('/new', name: 'admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $menu = new Menu();
        // Explicitly set a default meal type to avoid null values
        $menu->setMealType('main');
        
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Double-check that meal_type is set before persisting
            if (!$menu->getMealType()) {
                $menu->setMealType('main');
            }
            
            $this->entityManager->persist($menu);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le menu a été créé avec succès.');
            return $this->redirectToRoute('admin_menu_index');
        }

        return $this->render('admin/menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_menu_show', methods: ['GET'])]
    public function show(Menu $menu): Response
    {
        return $this->render('admin/menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu): Response
    {
        // Ensure there's always a meal type set
        if (!$menu->getMealType()) {
            $menu->setMealType('main');
        }
        
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Double-check again before flushing
            if (!$menu->getMealType()) {
                $menu->setMealType('main');
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le menu a été modifié avec succès.');
            return $this->redirectToRoute('admin_menu_index');
        }

        return $this->render('admin/menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($menu);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le menu a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_menu_index');
    }
}
