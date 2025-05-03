<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/category')]
#[IsGranted('ROLE_ADMIN')]
class AdminCategoryController extends AbstractController
{
    #[Route('/', name: 'app_admin_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findBy([], ['position' => 'ASC']);
        
        return $this->render('admin/menu/categories.html.twig', [
            'categories' => $categories,
        ]);
    }
    
    #[Route('/create', name: 'app_admin_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $name = $request->request->get('name');
        $description = $request->request->get('description');
        $position = $request->request->get('position');
        
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $category->setPosition($position);
        
        $entityManager->persist($category);
        $entityManager->flush();
        
        $this->addFlash('success', 'La catégorie a été créée avec succès.');
        
        return $this->redirectToRoute('app_admin_category');
    }
    
    #[Route('/update', name: 'app_admin_category_update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $description = $request->request->get('description');
        $position = $request->request->get('position');
        
        $category = $categoryRepository->find($id);
        
        if (!$category) {
            $this->addFlash('error', 'Catégorie non trouvée.');
            return $this->redirectToRoute('app_admin_category');
        }
        
        $category->setName($name);
        $category->setDescription($description);
        $category->setPosition($position);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'La catégorie a été mise à jour avec succès.');
        
        return $this->redirectToRoute('app_admin_category');
    }
    
    #[Route('/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            // Check if category has dishes
            if (count($category->getDishes()) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient des plats.');
                return $this->redirectToRoute('app_admin_category');
            }
            
            $entityManager->remove($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été supprimée.');
        }
        
        return $this->redirectToRoute('app_admin_category');
    }
    
    #[Route('/reorder', name: 'app_admin_category_reorder', methods: ['POST'])]
    public function reorder(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$this->isCsrfTokenValid('reorder_categories', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }
        
        $categoryId = $data['categoryId'] ?? null;
        $newPosition = $data['newPosition'] ?? null;
        
        if (!$categoryId || $newPosition === null) {
            return $this->json(['success' => false, 'message' => 'Missing required data'], 400);
        }
        
        $category = $categoryRepository->find($categoryId);
        
        if (!$category) {
            return $this->json(['success' => false, 'message' => 'Category not found'], 404);
        }
        
        $oldPosition = $category->getPosition();
        
        // Update positions of other categories
        if ($newPosition > $oldPosition) {
            // Moving down - shift categories in between up
            $entityManager->createQuery('
                UPDATE App\Entity\Category c
                SET c.position = c.position - 1
                WHERE c.position <= :newPos AND c.position > :oldPos AND c.id != :id
            ')
            ->setParameters([
                'newPos' => $newPosition,
                'oldPos' => $oldPosition,
                'id' => $categoryId
            ])
            ->execute();
        } elseif ($newPosition < $oldPosition) {
            // Moving up - shift categories in between down
            $entityManager->createQuery('
                UPDATE App\Entity\Category c
                SET c.position = c.position + 1
                WHERE c.position >= :newPos AND c.position < :oldPos AND c.id != :id
            ')
            ->setParameters([
                'newPos' => $newPosition,
                'oldPos' => $oldPosition,
                'id' => $categoryId
            ])
            ->execute();
        }
        
        // Update the category's position
        $category->setPosition($newPosition);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
}
