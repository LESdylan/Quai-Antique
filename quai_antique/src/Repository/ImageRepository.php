<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * Find active images by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.category = :category')
            ->andWhere('i.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured images (active ones with no specific dish association)
     */
    public function findFeaturedImages(int $limit = 6): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.dish IS NULL')
            ->andWhere('i.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search images with filters and pagination
     */
    public function search(array $criteria = [], int $page = 1, int $limit = 24): array
    {
        $qb = $this->createQueryBuilder('i')
            ->orderBy('i.createdAt', 'DESC');
            
        // Apply criteria filters
        if (!empty($criteria['category'])) {
            $qb->andWhere('i.category = :category')
               ->setParameter('category', $criteria['category']);
        }
        
        if (!empty($criteria['purpose'])) {
            $qb->andWhere('i.purpose = :purpose')
               ->setParameter('purpose', $criteria['purpose']);
        }
        
        if (isset($criteria['isActive'])) {
            $qb->andWhere('i.isActive = :isActive')
               ->setParameter('isActive', $criteria['isActive']);
        }
        
        if (!empty($criteria['search'])) {
            $qb->andWhere('i.title LIKE :search OR i.alt LIKE :search OR i.description LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }
        
        if (!empty($criteria['tag'])) {
            $qb->leftJoin('i.tags', 't')
               ->andWhere('t.slug = :tag')
               ->setParameter('tag', $criteria['tag']);
        }
        
        if (!empty($criteria['dish'])) {
            $qb->andWhere('i.dish = :dish')
               ->setParameter('dish', $criteria['dish']);
        }
        
        // Add pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
           
        // Get paginator
        $paginator = new Paginator($qb);
        
        // Calculate total pages
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'images' => $paginator,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }
    
    /**
     * Get distinct categories
     */
    public function findAllCategories(): array
    {
        return $this->createQueryBuilder('i')
            ->select('DISTINCT i.category')
            ->where('i.category IS NOT NULL')
            ->getQuery()
            ->getScalarResult();
    }
}
