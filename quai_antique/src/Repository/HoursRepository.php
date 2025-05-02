<?php

namespace App\Repository;

use App\Entity\Hours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hours>
 *
 * @method Hours|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hours|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hours[]    findAll()
 * @method Hours[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hours::class);
    }

    /**
     * Find hours for a specific day of week
     */
    public function findByDayOfWeek(int $dayOfWeek): ?Hours
    {
        return $this->findOneBy(['dayOfWeek' => $dayOfWeek]);
    }

    /**
     * Find all opening hours ordered by day of week
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['dayOfWeek' => 'ASC']);
    }
    
    /**
     * Find all days when the restaurant is open
     */
    public function findOpenDays(): array
    {
        return $this->findBy(['isClosed' => false], ['dayOfWeek' => 'ASC']);
    }
}
