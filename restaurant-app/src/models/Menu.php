<?php

class Menu {
    private $id;
    private $title;
    private $description;

    public function __construct($id, $title, $description) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function save() {
        // Code to save the menu to the database
    }

    public function delete() {
        // Code to delete the menu from the database
    }

    public static function findAll() {
        // Code to retrieve all menus from the database
    }

    public static function findById($id) {
        // Code to retrieve a menu by its ID from the database
    }
}