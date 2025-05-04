<?php

namespace App\Repository;

use App\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 *
 * @method MenuItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method MenuItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method MenuItem[]    findAll()
 * @method MenuItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /**
     * Find popular menu items
     *
     * @return MenuItem[] Returns an array of MenuItem objects
     */
    public function findPopularItems(int $limit = 5): array
    {
        try {
            return $this->createQueryBuilder('m')
                ->orderBy('m.id', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}
