<?php

class AdminController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function manageGallery() {
        // Logic to manage the image gallery
        // This includes adding, editing, and deleting images
    }

    public function manageMenu() {
        // Logic to manage the menu
        // This includes adding, editing, and deleting dishes and categories
    }

    public function manageReservations() {
        // Logic to manage reservations
        // This includes viewing, modifying, and deleting reservations
    }
}