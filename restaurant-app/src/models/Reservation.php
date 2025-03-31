<?php

class Reservation {
    private $id;
    private $userId;
    private $date;
    private $time;
    private $numberOfCovers;
    private $allergies;

    public function __construct($userId, $date, $time, $numberOfCovers, $allergies = '') {
        $this->userId = $userId;
        $this->date = $date;
        $this->time = $time;
        $this->numberOfCovers = $numberOfCovers;
        $this->allergies = $allergies;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getDate() {
        return $this->date;
    }

    public function getTime() {
        return $this->time;
    }

    public function getNumberOfCovers() {
        return $this->numberOfCovers;
    }

    public function getAllergies() {
        return $this->allergies;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function setNumberOfCovers($numberOfCovers) {
        $this->numberOfCovers = $numberOfCovers;
    }

    public function setAllergies($allergies) {
        $this->allergies = $allergies;
    }

    public function save() {
        // Code to save the reservation to the database
    }

    public function update() {
        // Code to update the reservation in the database
    }

    public function delete() {
        // Code to delete the reservation from the database
    }

    public static function findById($id) {
        // Code to find a reservation by its ID
    }

    public static function findByUserId($userId) {
        // Code to find reservations by user ID
    }
}