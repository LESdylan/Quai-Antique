<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Dish;
use App\Repository\CategoryRepository;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use App\Repository\ImageRepository;
use App\Service\MenuService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MenuController extends AbstractController
{
    #[Route('/la-carte', name: 'app_menu')]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        DishRepository $dishRepository,
        MenuRepository $menuRepository,
        ImageRepository $imageRepository
    ): Response {
        $categories = $categoryRepository->findBy([], ['position' => 'ASC']);
        
        // Get active filter
        $categoryFilter = $request->query->get('category');
        $allergenFilter = $request->query->get('allergen');
        
        // Get dishes with filters
        if ($categoryFilter) {
            $category = $categoryRepository->find($categoryFilter);
            $dishes = $dishRepository->findBy(['category' => $category, 'isActive' => true]);
        } elseif ($allergenFilter) {
            // This would require a custom query in your repository
            $dishes = $dishRepository->findByAllergenExclusion($allergenFilter);
        } else {
            $dishes = $dishRepository->findBy(['isActive' => true], ['category' => 'ASC']);
        }
        
        // Group dishes by category
        $dishesByCategory = [];
        foreach ($dishes as $dish) {
            $categoryId = $dish->getCategory()->getId();
            if (!isset($dishesByCategory[$categoryId])) {
                $dishesByCategory[$categoryId] = [
                    'category' => $dish->getCategory(),
                    'dishes' => []
                ];
            }
            $dishesByCategory[$categoryId]['dishes'][] = $dish;
        }
        
        // Group dishes by category with associated images
        $categoriesWithDishes = [];
        foreach ($dishes as $dish) {
            $category = $dish->getCategory();
            if (!$category) continue;
            
            $categoryId = $category->getId();
            if (!isset($categoriesWithDishes[$categoryId])) {
                $categoriesWithDishes[$categoryId] = [
                    'category' => $category,
                    'dishes' => []
                ];
            }
            $categoriesWithDishes[$categoryId]['dishes'][] = $dish;
        }
        
        // Get menus
        $menus = $menuRepository->findBy(['isActive' => true]);
        
        // Get featured images for each category
        $categoryImages = [];
        foreach ($categories as $category) {
            // Get images tagged with this category's name
            $categoryName = strtolower($category->getName());
            $images = $imageRepository->findByCategory($categoryName);
            if (count($images) > 0) {
                $categoryImages[$category->getId()] = $images;
            }
        }
        
        // Get featured menu images
        $menuImages = $imageRepository->findByCategory('menu');
        
        // Get gallery images
        $galleryImages = $imageRepository->findByCategory('gallery');
        
        return $this->render('menu/index.html.twig', [
            'categories' => $categories,
            'dishesByCategory' => $dishesByCategory,
            'menus' => $menus,
            'activeCategory' => $categoryFilter,
            'activeAllergen' => $allergenFilter,
            'categoriesWithDishes' => $categoriesWithDishes,
            'categoryImages' => $categoryImages,
            'menuImages' => $menuImages,
            'galleryImages' => $galleryImages,
        ]);
    }
    
    #[Route('/menu/filter', name: 'app_menu_filter')]
    public function filter(
        Request $request,
        CategoryRepository $categoryRepository,
        DishRepository $dishRepository
    ): Response {
        $categoryId = $request->query->get('category');
        $allergen = $request->query->get('allergen');
        $seasonal = $request->query->has('seasonal');
        
        // Apply filters
        $criteria = ['isActive' => true];
        
        if ($categoryId && $categoryId !== 'all') {
            $category = $categoryRepository->find($categoryId);
            $criteria['category'] = $category;
        }
        
        if ($seasonal) {
            $criteria['isSeasonal'] = true;
        }
        
        $dishes = $dishRepository->findBy($criteria);
        
        // If allergen filter is active, apply it as post-filter
        if ($allergen && $allergen !== '') {
            $dishes = array_filter($dishes, function($dish) use ($allergen) {
                foreach ($dish->getAllergens() as $dishAllergen) {
                    if ($dishAllergen->getName() === $allergen) {
                        return false;
                    }
                }
                return true;
            });
        }
        
        return $this->render('menu/partials/dishes_list.html.twig', [
            'dishes' => $dishes
        ]);
    }

    #[Route('/detail/{id}', name: 'app_menu_dish_detail')]
    public function dishDetail(Dish $dish): Response
    {
        return $this->render('menu/dish_detail.html.twig', [
            'dish' => $dish,
        ]);
    }
}
