<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 *
 * @method Menu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Menu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Menu[]    findAll()
 * @method Menu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function save(Menu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Menu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find menus based on filtering criteria
     * 
     * @param array $filters Associative array of filters to apply
     * @return Menu[]
     */
    public function findByFilters(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('m');
        
        // Join with category if needed for filtering or sorting
        if (!empty($filters['category'])) {
            $queryBuilder
                ->leftJoin('m.category', 'c')
                ->addSelect('c');
                
            $queryBuilder
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $filters['category']);
        }
        
        // Apply search filter for title or description
        if (!empty($filters['search'])) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like('m.title', ':search'),
                        $queryBuilder->expr()->like('m.description', ':search')
                    )
                )
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        // Apply active/inactive filter
        if (isset($filters['isActive'])) {
            $queryBuilder
                ->andWhere('m.isActive = :isActive')
                ->setParameter('isActive', $filters['isActive']);
        }
        
        // Apply price range filter
        if (!empty($filters['minPrice'])) {
            $queryBuilder
                ->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice'] * 100); // Convert to cents
        }
        
        if (!empty($filters['maxPrice'])) {
            $queryBuilder
                ->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice'] * 100); // Convert to cents
        }
        
        // Default sort by position then by title
        $queryBuilder->orderBy('m.position', 'ASC')
                    ->addOrderBy('m.title', 'ASC');
        
        // Apply custom sorting if specified
        if (!empty($filters['sortBy'])) {
            $direction = !empty($filters['sortDirection']) ? $filters['sortDirection'] : 'ASC';
            $sortField = 'm.' . $filters['sortBy'];
            
            // Special case for sorting by category name
            if ($filters['sortBy'] === 'category') {
                if (!$queryBuilder->getDQLPart('join')) {
                    $queryBuilder->leftJoin('m.category', 'c')->addSelect('c');
                }
                $sortField = 'c.name';
            }
            
            $queryBuilder->orderBy($sortField, $direction);
        }
        
        return $queryBuilder->getQuery()->getResult();
    }
}
