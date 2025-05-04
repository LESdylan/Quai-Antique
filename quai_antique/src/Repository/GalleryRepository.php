<?php

namespace App\Repository;

use App\Entity\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gallery>
 *
 * @method Gallery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gallery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gallery[]    findAll()
 * @method Gallery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalleryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    public function save(Gallery $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Gallery $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    /**
     * Find gallery items by category ordered by position
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.category = :category')
            ->setParameter('category', $category)
            ->orderBy('g.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find random gallery items limited by count
     */
    public function findRandom(int $limit = 6): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id FROM gallery ORDER BY RAND() LIMIT ' . $limit;
        
        try {
            $stmt = $conn->prepare($sql);
            $resultSet = $stmt->executeQuery();
            $ids = array_column($resultSet->fetchAllAssociative(), 'id');
            
            if (empty($ids)) {
                return [];
            }
            
            return $this->createQueryBuilder('g')
                ->where('g.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get next available position
     */
    public function getNextPosition(): int
    {
        $result = $this->createQueryBuilder('g')
            ->select('MAX(g.position)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? $result + 1 : 0;
    }

    /**
     * Find gallery images with pagination
     *
     * @param int $page The current page
     * @param int $limit The maximum number of results per page
     * @param string|null $category Filter by category
     * @return array with pagination data
     */
    public function findPaginated(int $page = 1, int $limit = 25, ?string $category = null): array
    {
        $query = $this->createQueryBuilder('g')
            ->orderBy('g.position', 'ASC')
            ->addOrderBy('g.createdAt', 'DESC');
            
        if ($category) {
            $query->andWhere('g.category = :category')
                  ->setParameter('category', $category);
        }
        
        $query->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);
        
        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $maxPages = ceil($totalItems / $limit);
        
        return [
            'data' => $paginator,
            'currentPage' => $page,
            'maxPages' => $maxPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $limit
        ];
    }
}
