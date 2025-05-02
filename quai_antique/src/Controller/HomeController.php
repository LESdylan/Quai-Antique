<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'page_title' => 'Bienvenue au Quai Antique',
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
