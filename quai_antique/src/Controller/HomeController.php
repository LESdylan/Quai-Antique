<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use App\Repository\RestaurantRepository;
use App\Repository\DishRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ImageRepository $imageRepository, RestaurantRepository $restaurantRepository, DishRepository $dishRepository): Response
    {
        // Get hero image - check all possible hero banner purpose values
        $heroImage = $imageRepository->findOneBy(['purpose' => 'hero', 'isActive' => true]);
        
        // If not found, try with other possible values
        if (!$heroImage) {
            $heroImage = $imageRepository->findOneBy(['purpose' => 'hero_banner', 'isActive' => true]);
        }
        
        // If still not found, try with 'Hero Banner'
        if (!$heroImage) {
            $heroImage = $imageRepository->findOneBy(['purpose' => 'Hero Banner', 'isActive' => true]);
        }
        
        // Get quote background image
        $quoteBackgroundImage = $imageRepository->findOneBy(['purpose' => 'quote_background', 'isActive' => true]);
        
        // Get chef image
        $chefImage = $imageRepository->findOneBy(['purpose' => 'chef', 'isActive' => true]);
        
        // Get restaurant info
        $restaurant = $restaurantRepository->findOneBy([]);
        
        // Get featured dishes for homepage
        $featuredDishes = $dishRepository->findBy(['isFeatured' => true, 'isActive' => true], ['createdAt' => 'DESC'], 3);
        
        return $this->render('home/index.html.twig', [
            'hero_image' => $heroImage,
            'quote_background_image' => $quoteBackgroundImage,
            'chef_image' => $chefImage,
            'restaurant' => $restaurant,
            'featured_dishes' => $featuredDishes,
        ]);
    }

    #[Route('/test', name: 'app_test')]
    public function test(): Response
    {
        return $this->render('home/test.html.twig', [
            'controller_name' => 'HomeController',
            'page_title' => 'Page de test',
        ]);
    }

    #[Route('/debug-database', name: 'app_debug_database')]
    public function debugDatabase(EntityManagerInterface $entityManager): Response
    {
        $conn = $entityManager->getConnection();
        
        // Get all tables
        $tables = $conn->executeQuery("SHOW TABLES")->fetchAllAssociative();
        
        $structure = [];
        
        foreach ($tables as $tableRow) {
            $table = reset($tableRow); // Get the first value (table name)
            
            // Get columns for this table
            $columns = $conn->executeQuery("SHOW COLUMNS FROM `$table`")->fetchAllAssociative();
            $structure[$table] = $columns;
        }
        
        return $this->render('home/debug.html.twig', [
            'database_structure' => $structure
        ]);
    }
}
