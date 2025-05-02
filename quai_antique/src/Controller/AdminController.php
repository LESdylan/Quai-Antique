<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Dish;
use App\Entity\Menu;
use App\Entity\Reservation;
use App\Entity\Restaurant;
use App\Form\CategoryType;
use App\Form\DishType;
use App\Form\MenuType;
use App\Form\RestaurantSettingsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $reservationsToday = $this->entityManager->getRepository(Reservation::class)
            ->findByDate(new \DateTime());
        
        $dishesCount = $this->entityManager->getRepository(Dish::class)->count([]);
        $categoriesCount = $this->entityManager->getRepository(Category::class)->count([]);
        $menusCount = $this->entityManager->getRepository(Menu::class)->count([]);
        
        return $this->render('admin/dashboard.html.twig', [
            'reservationsToday' => $reservationsToday,
            'dishesCount' => $dishesCount,
            'categoriesCount' => $categoriesCount,
            'menusCount' => $menusCount,
        ]);
    }

    #[Route('/settings', name: 'app_admin_settings')]
    public function settings(Request $request): Response
    {
        $restaurant = $this->entityManager->getRepository(Restaurant::class)->findOneBy([]) 
            ?? new Restaurant();
        
        $form = $this->createForm(RestaurantSettingsType::class, $restaurant);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($restaurant);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Les paramètres du restaurant ont été mis à jour.');
            
            return $this->redirectToRoute('app_admin_settings');
        }
        
        return $this->render('admin/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservations', name: 'app_admin_reservations')]
    public function reservations(Request $request): Response
    {
        $date = $request->query->get('date') ? new \DateTime($request->query->get('date')) : new \DateTime();
        
        $reservations = $this->entityManager->getRepository(Reservation::class)
            ->findByDate($date);
        
        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'date' => $date,
        ]);
    }

    #[Route('/menu', name: 'app_admin_menu')]
    public function menu(): Response
    {
        $categories = $this->entityManager->getRepository(Category::class)
            ->findBy([], ['position' => 'ASC']);
        
        $dishes = $this->entityManager->getRepository(Dish::class)
            ->findBy([], ['category' => 'ASC', 'name' => 'ASC']);
        
        $menus = $this->entityManager->getRepository(Menu::class)
            ->findBy([], ['title' => 'ASC']);
        
        return $this->render('admin/menu.html.twig', [
            'categories' => $categories,
            'dishes' => $dishes,
            'menus' => $menus,
        ]);
    }
}
