<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /**
     * Find currently active promotions
     */
    public function findActive(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now->format('Y-m-d'))
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active promotions by type
     */
    public function findActiveByType(string $type): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.type = :type')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('active', true)
            ->setParameter('type', $type)
            ->setParameter('now', $now->format('Y-m-d'))
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find active promotions for the current date
     */
    public function findCurrentlyActive(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now->format('Y-m-d'))
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find all promotions ordered by activity status and date
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.isActive', 'DESC')
            ->addOrderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
