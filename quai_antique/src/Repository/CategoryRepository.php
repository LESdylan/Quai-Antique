<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Example of a fixed query method that was causing errors
    public function findAllWithDishes(): array
    {
        return $this->createQueryBuilder('c')
            // Use the actual column name from your database instead of 'name'
            // For example, if the column is actually called 'title' in the database:
            ->select('c.id', 'c.title', 'c.description', 'c.position') 
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
