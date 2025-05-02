<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Restaurant;
use App\Entity\Table;
use App\Repository\HoursRepository;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantRepository;
use App\Repository\TableRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    private $reservationRepository;
    private $tableRepository;
    private $hoursRepository;
    private $restaurantRepository;
    private $entityManager;

    public function __construct(
        ReservationRepository $reservationRepository,
        TableRepository $tableRepository,
        HoursRepository $hoursRepository,
        RestaurantRepository $restaurantRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->tableRepository = $tableRepository;
        $this->hoursRepository = $hoursRepository;
        $this->restaurantRepository = $restaurantRepository;
        $this->entityManager = $entityManager;
    }

    public function getRestaurantMaxCapacity(): int
    {
        $restaurant = $this->restaurantRepository->findOneBy([]);
        return $restaurant ? $restaurant->getMaxGuests() : 50; // Default to 50 if not set
    }

    public function createTimeSlots(\DateTime $date): array
    {
        // Get restaurant hours for the given day
        $dayOfWeek = (int)$date->format('N'); // 1 (Monday) to 7 (Sunday)
        $hours = $this->hoursRepository->findOneBy(['dayOfWeek' => $dayOfWeek]);
        
        if (!$hours || $hours->isIsClosed()) {
            return [];
        }

        $timeSlots = [];
        
        // Create lunch time slots if lunch hours are set
        if ($hours->getLunchOpeningTime() && $hours->getLunchClosingTime()) {
            $start = clone $hours->getLunchOpeningTime();
            $end = clone $hours->getLunchClosingTime();
            
            // Convert time objects to timestamps for comparison
            $startTime = $start->getTimestamp();
            $endTime = $end->getTimestamp();
            
            // Add time slots in 15-minute increments
            while ($startTime < $endTime) {
                $timeSlot = new \DateTime();
                $timeSlot->setTimestamp($startTime);
                $timeSlots[] = $timeSlot->format('H:i');
                $startTime += 15 * 60; // Add 15 minutes
            }
        }
        
        // Create dinner time slots if dinner hours are set
        if ($hours->getDinnerOpeningTime() && $hours->getDinnerClosingTime()) {
            $start = clone $hours->getDinnerOpeningTime();
            $end = clone $hours->getDinnerClosingTime();
            
            // Convert time objects to timestamps for comparison
            $startTime = $start->getTimestamp();
            $endTime = $end->getTimestamp();
            
            // Add time slots in 15-minute increments
            while ($startTime < $endTime) {
                $timeSlot = new \DateTime();
                $timeSlot->setTimestamp($startTime);
                $timeSlots[] = $timeSlot->format('H:i');
                $startTime += 15 * 60; // Add 15 minutes
            }
        }
        
        return $timeSlots;
    }

    public function checkAvailability(\DateTime $date, string $timeSlot, int $guestCount): bool
    {
        // Get the restaurant's maximum capacity
        $maxCapacity = $this->getRestaurantMaxCapacity();
        
        // Get existing reservations for the selected date and time
        $existingReservations = $this->reservationRepository->findByDateAndTime($date, $timeSlot);
        
        // Calculate total guests already booked
        $bookedGuests = 0;
        foreach ($existingReservations as $reservation) {
            $bookedGuests += $reservation->getGuestCount();
        }
        
        // Check if adding the new guests would exceed capacity
        return ($bookedGuests + $guestCount) <= $maxCapacity;
    }

    public function getAvailableTables(\DateTime $date, string $timeSlot, int $guestCount): array
    {
        // Get all active tables
        $allTables = $this->tableRepository->findBy(['isActive' => true]);
        
        // Get reservations for this date and time
        $existingReservations = $this->reservationRepository->findByDateAndTime($date, $timeSlot);
        
        // Get tables that are already booked for this time
        $bookedTableIds = [];
        foreach ($existingReservations as $reservation) {
            foreach ($reservation->getTables() as $table) {
                $bookedTableIds[] = $table->getId();
            }
        }
        
        // Filter out already booked tables
        $availableTables = array_filter($allTables, function($table) use ($bookedTableIds) {
            return !in_array($table->getId(), $bookedTableIds);
        });
        
        // Filter tables with enough seats
        return array_filter($availableTables, function($table) use ($guestCount) {
            return $table->getSeats() >= $guestCount;
        });
    }

    public function createReservation(array $data): Reservation
    {
        $reservation = new Reservation();
        $reservation->setFirstName($data['firstName']);
        $reservation->setLastName($data['lastName']);
        $reservation->setEmail($data['email']);
        $reservation->setPhone($data['phone']);
        $reservation->setGuestCount($data['guestCount']);
        
        // Set date from the date string
        $date = new \DateTime($data['date']);
        $reservation->setDate($date);
        
        // Set time slot
        $reservation->setTimeSlot($data['timeSlot']);
        
        // Set notes and allergies if provided
        if (!empty($data['notes'])) {
            $reservation->setNotes($data['notes']);
        }
        
        if (!empty($data['allergies'])) {
            $reservation->setAllergies($data['allergies']);
        }
        
        // Set user if provided
        if (!empty($data['user'])) {
            $reservation->setUser($data['user']);
        }
        
        // Assign tables if provided
        if (!empty($data['tables'])) {
            foreach ($data['tables'] as $table) {
                $reservation->addTable($table);
            }
        }
        
        // Set default status
        $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        
        // Save to database
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
        
        return $reservation;
    }
}
