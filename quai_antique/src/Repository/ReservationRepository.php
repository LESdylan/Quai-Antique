<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function save(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return int Returns the count of upcoming reservations
     */
    public function countUpcomingReservations(): int
    {
        try {
            return $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->andWhere('r.date >= :today')
                ->setParameter('today', new \DateTime('today'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            // In case the reservation table doesn't exist yet
            return 0;
        }
    }
    
    /**
     * @return int Returns the count of today's reservations
     */
    public function countTodayReservations(): int
    {
        try {
            $today = new \DateTime('today');
            $tomorrow = new \DateTime('tomorrow');
            
            return $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->andWhere('r.date >= :today')
                ->andWhere('r.date < :tomorrow')
                ->setParameter('today', $today)
                ->setParameter('tomorrow', $tomorrow)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            // In case the reservation table doesn't exist yet
            return 0;
        }
    }
    
    /**
     * Find upcoming reservations
     * 
     * @return Reservation[] Returns an array of upcoming reservations
     */
    public function findUpcomingReservations(int $limit = 5): array
    {
        try {
            return $this->createQueryBuilder('r')
                ->andWhere('r.date >= :now')
                ->setParameter('now', new \DateTime())
                ->orderBy('r.date', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Find reservations by day of week for statistics
     * 
     * @return array Returns an array with counts by day of week
     */
    public function findReservationsByDayOfWeek(): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                SELECT 
                    DAYOFWEEK(r.date) as day_of_week,
                    COUNT(r.id) as count
                FROM reservation r
                GROUP BY DAYOFWEEK(r.date)
                ORDER BY day_of_week
            ';
            
            $stmt = $conn->prepare($sql);
            $resultSet = $stmt->executeQuery();
            
            // Initialize days array with zeros
            $days = [0, 0, 0, 0, 0, 0, 0];
            
            // Fill with actual data
            foreach ($resultSet->fetchAllAssociative() as $row) {
                // DAYOFWEEK returns 1 for Sunday, 2 for Monday, etc.
                // Adjust to 0-based index and handle Sunday = 6
                $index = ($row['day_of_week'] - 2 + 7) % 7;
                $days[$index] = (int) $row['count'];
            }
            
            return $days;
        } catch (\Exception $e) {
            // Return zeros for all days in case of error
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    /**
     * Find reservations based on filters
     * 
     * @param array $filters Associative array of filters to apply
     * @return Reservation[]
     */
    public function findByFilters(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        // Apply search filter (for name, email, or phone number)
        if (!empty($filters['search'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('r.name', ':search'),
                    $queryBuilder->expr()->like('r.email', ':search'),
                    $queryBuilder->expr()->like('r.phone', ':search')
                )
            )
            ->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        // Apply status filter - make sure we handle the 'all' case specially
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $queryBuilder
                ->andWhere('r.status = :status')
                ->setParameter('status', $filters['status']);
        }
        
        // Apply date filter
        if (!empty($filters['date'])) {
            $queryBuilder
                ->andWhere('r.date = :date')
                ->setParameter('date', $filters['date']);
        }

        // Default sort by date and time
        $queryBuilder->orderBy('r.date', 'DESC');
        
        if (!empty($filters['timeSlot'])) {
            $queryBuilder->addOrderBy('r.timeSlot', 'ASC');
        }
        
        return $queryBuilder->getQuery()->getResult();
    }
}
