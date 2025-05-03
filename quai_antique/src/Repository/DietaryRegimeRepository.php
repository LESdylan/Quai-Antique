<?php

namespace App\Repository;

use App\Entity\DietaryRegime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DietaryRegime>
 */
class DietaryRegimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DietaryRegime::class);
    }
}
