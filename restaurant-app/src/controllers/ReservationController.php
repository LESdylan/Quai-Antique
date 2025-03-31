<?php

class ReservationController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createReservation($userId, $date, $time, $numberOfCovers, $allergies) {
        // Check availability
        if ($this->checkAvailability($date, $time, $numberOfCovers)) {
            // Prepare and execute the reservation query
            $query = "INSERT INTO reservations (user_id, date, time, number_of_covers, allergies) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("issis", $userId, $date, $time, $numberOfCovers, $allergies);
            return $stmt->execute();
        }
        return false; // Not available
    }

    public function viewReservations($userId) {
        $query = "SELECT * FROM reservations WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkAvailability($date, $time, $numberOfCovers) {
        $query = "SELECT SUM(number_of_covers) as total_covers FROM reservations WHERE date = ? AND time = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $date, $time);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $totalCovers = $result['total_covers'] ?? 0;

        // Assuming a maximum capacity defined in the configuration
        $maxCapacity = 20; // This should be fetched from the configuration or database
        return ($totalCovers + $numberOfCovers) <= $maxCapacity;
    }
}