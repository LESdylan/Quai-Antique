<?php

namespace App\Twig;

use App\Entity\Restaurant;
use App\Repository\HoursRepository;
use App\Repository\RestaurantRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RestaurantExtension extends AbstractExtension implements GlobalsInterface
{
    private $restaurantRepository;
    private $hoursRepository;
    private ?Restaurant $restaurant = null;

    public function __construct(RestaurantRepository $restaurantRepository, HoursRepository $hoursRepository)
    {
        $this->restaurantRepository = $restaurantRepository;
        $this->hoursRepository = $hoursRepository;
    }

    public function getGlobals(): array
    {
        // Load restaurant data only once
        if ($this->restaurant === null) {
            $this->restaurant = $this->restaurantRepository->findOneBy([]) ?? new Restaurant();
        }

        // Get restaurant hours
        $hours = $this->hoursRepository->findAllOrdered();

        return [
            'restaurant' => $this->restaurant,
            'hours' => $hours
        ];
    }
}
