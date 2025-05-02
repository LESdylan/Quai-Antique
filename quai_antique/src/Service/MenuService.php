<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Dish;
use App\Entity\Menu;
use App\Repository\CategoryRepository;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;

class MenuService
{
    private $dishRepository;
    private $categoryRepository;
    private $menuRepository;
    private $entityManager;

    public function __construct(
        DishRepository $dishRepository,
        CategoryRepository $categoryRepository,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->dishRepository = $dishRepository;
        $this->categoryRepository = $categoryRepository;
        $this->menuRepository = $menuRepository;
        $this->entityManager = $entityManager;
    }

    public function getAllCategories(): array
    {
        return $this->categoryRepository->findBy([], ['position' => 'ASC']);
    }
    
    public function getActiveCategories(): array
    {
        // Get all categories that have at least one active dish
        $categories = $this->categoryRepository->findBy([], ['position' => 'ASC']);
        
        return array_filter($categories, function(Category $category) {
            foreach ($category->getDishes() as $dish) {
                if ($dish->isIsActive()) {
                    return true;
                }
            }
            return false;
        });
    }
    
    public function getCategoryWithDishes(Category $category): array
    {
        return [
            'category' => $category,
            'dishes' => $this->dishRepository->findBy(['category' => $category, 'isActive' => true], ['name' => 'ASC'])
        ];
    }
    
    public function getAllCategoriesWithDishes(): array
    {
        $result = [];
        $categories = $this->getActiveCategories();
        
        foreach ($categories as $category) {
            $result[] = $this->getCategoryWithDishes($category);
        }
        
        return $result;
    }

    public function getAllMenus(): array
    {
        return $this->menuRepository->findBy(['isActive' => true], ['title' => 'ASC']);
    }
    
    public function getMenuWithDishes(Menu $menu): array
    {
        return [
            'menu' => $menu,
            'dishes' => $menu->getDishes()->toArray()
        ];
    }
    
    public function getAllMenusWithDishes(): array
    {
        $result = [];
        $menus = $this->getAllMenus();
        
        foreach ($menus as $menu) {
            $result[] = $this->getMenuWithDishes($menu);
        }
        
        return $result;
    }

    public function getDishesByAllergen(?string $allergen = null): array
    {
        if (!$allergen) {
            return $this->dishRepository->findBy(['isActive' => true]);
        }
        
        return $this->dishRepository->findByAllergen($allergen);
    }

    public function getDishesWithFilters(array $filters = []): array
    {
        $criteria = ['isActive' => true];
        
        if (!empty($filters['category'])) {
            $criteria['category'] = $filters['category'];
        }
        
        if (!empty($filters['seasonal']) && $filters['seasonal'] === true) {
            $criteria['isSeasonal'] = true;
        }
        
        return $this->dishRepository->findBy($criteria);
    }
}
