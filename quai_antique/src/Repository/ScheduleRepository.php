<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 *
 * @method Schedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Schedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Schedule[]    findAll()
 * @method Schedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function save(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    /**
     * Find active schedules ordered by day of week and meal type
     */
    public function findActiveSchedules(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.dayOfWeek', 'ASC')
            ->addOrderBy('s.mealType', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find schedules for a specific day
     */
    public function findByDayOfWeek(int $dayOfWeek): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.dayOfWeek = :dayOfWeek')
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->orderBy('s.mealType', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Check if restaurant is open at a specific datetime
     */
    public function isOpenAt(\DateTime $dateTime): bool
    {
        $dayOfWeek = (int) $dateTime->format('N'); // 1 (Monday) to 7 (Sunday)
        $time = $dateTime->format('H:i:s');
        
        $qb = $this->createQueryBuilder('s')
            ->where('s.dayOfWeek = :dayOfWeek')
            ->andWhere('s.isActive = :active')
            ->andWhere('TIME(:time) BETWEEN s.openingTime AND s.closingTime')
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->setParameter('active', true)
            ->setParameter('time', $time);
        
        $result = $qb->getQuery()->getOneOrNullResult();
        
        return $result !== null;
    }
}
