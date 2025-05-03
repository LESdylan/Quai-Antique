<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
