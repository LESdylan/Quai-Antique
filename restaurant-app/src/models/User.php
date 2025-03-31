<?php

class User {
    private $id;
    private $name;
    private $email;
    private $password;
    private $allergies;
    private $defaultGuests;

    public function __construct($name, $email, $password, $allergies = '', $defaultGuests = 1) {
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->allergies = $allergies;
        $this->defaultGuests = $defaultGuests;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    public function getAllergies() {
        return $this->allergies;
    }

    public function getDefaultGuests() {
        return $this->defaultGuests;
    }

    public function setDefaultGuests($defaultGuests) {
        $this->defaultGuests = $defaultGuests;
    }

    public static function findByEmail($email) {
        // Logic to find a user by email in the database
    }

    public function save() {
        // Logic to save the user to the database
    }

    public function update() {
        // Logic to update user information in the database
    }

    public function delete() {
        // Logic to delete the user from the database
    }
}