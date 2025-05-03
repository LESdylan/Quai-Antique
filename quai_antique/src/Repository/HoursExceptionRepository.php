<?php

namespace App\Repository;

use App\Entity\HoursException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HoursException>
 */
class HoursExceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HoursException::class);
    }

    /**
     * Find exceptions for a specific date
     */
    public function findByDate(\DateTimeInterface $date): ?HoursException
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find upcoming exceptions
     */
    public function findUpcoming(int $limit = 5): array
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->setParameter('today', $today->format('Y-m-d'))
            ->orderBy('e.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find all exceptions ordered by date
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Delete all past exceptions
     */
    public function deletePastExceptions(): int
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.date < :today')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }

    /**
     * Find future exceptions
     */
    public function findFutureExceptions(): array
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->setParameter('today', $today)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
