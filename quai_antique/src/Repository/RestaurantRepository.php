<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * Finds the first restaurant record or creates a new default one
     */
    public function findOneOrCreate(): Restaurant
    {
        $restaurant = $this->findOneBy([]);
        
        if (!$restaurant) {
            $restaurant = new Restaurant();
            $restaurant->setName('Le Quai Antique');
            $restaurant->setDescription('Une expérience gastronomique unique à Chambéry, proposant une cuisine authentique et raffinée mettant en valeur les produits savoyards.');
            $restaurant->setAddress('12 Quai des Allobroges');
            $restaurant->setCity('Chambéry');
            $restaurant->setZipCode('73000');
            $restaurant->setPhone('04 79 85 XX XX');
            $restaurant->setEmail('contact@quaiantique.fr');
            
            $this->getEntityManager()->persist($restaurant);
            $this->getEntityManager()->flush();
        }
        
        return $restaurant;
    }
}
