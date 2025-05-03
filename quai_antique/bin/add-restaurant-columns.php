#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';
$dbPass = 'MO3848seven_36';
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

echo "Connecting to database: $dbName on $dbHost...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "\n--- Step 1: Checking restaurant table structure ---\n";

// First check if the restaurant table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'restaurant'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Restaurant table doesn't exist. Creating it now...\n";
        
        // Create the restaurant table with all required columns
        $pdo->exec("
            CREATE TABLE `restaurant` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `city` VARCHAR(64) DEFAULT NULL,
                `zip_code` VARCHAR(10) DEFAULT NULL,
                `phone` VARCHAR(20) DEFAULT NULL,
                `email` VARCHAR(255) DEFAULT NULL,
                `website` VARCHAR(255) DEFAULT NULL,
                `facebook_url` VARCHAR(255) DEFAULT NULL,
                `instagram_url` VARCHAR(255) DEFAULT NULL,
                `tripadvisor_url` VARCHAR(255) DEFAULT NULL,
                `average_price_lunch` DECIMAL(10,2) DEFAULT NULL,
                `average_price_dinner` DECIMAL(10,2) DEFAULT NULL,
                `longitude` VARCHAR(255) DEFAULT NULL,
                `latitude` VARCHAR(255) DEFAULT NULL,
                `logo_filename` VARCHAR(255) DEFAULT NULL,
                `display_opening_hours` TINYINT(1) DEFAULT 1,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        
        echo "Restaurant table created successfully with all required columns.\n";
        
        // Insert a default record
        $pdo->exec("
            INSERT INTO `restaurant` (`name`, `description`, `address`, `city`, `zip_code`, `phone`, `email`)
            VALUES ('Le Quai Antique', 'Une expérience gastronomique unique à Chambéry', '12 Quai des Allobroges', 'Chambéry', '73000', '04 79 85 XX XX', 'contact@quaiantique.fr')
        ");
        
        echo "Default restaurant record inserted.\n";
        
    } else {
        echo "Restaurant table exists. Checking for missing columns...\n";
        
        // List of columns that should exist in the restaurant table
        $requiredColumns = [
            'city' => 'ALTER TABLE `restaurant` ADD COLUMN `city` VARCHAR(64) DEFAULT NULL',
            'zip_code' => 'ALTER TABLE `restaurant` ADD COLUMN `zip_code` VARCHAR(10) DEFAULT NULL',
            'phone' => 'ALTER TABLE `restaurant` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL',
            'email' => 'ALTER TABLE `restaurant` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL',
            'website' => 'ALTER TABLE `restaurant` ADD COLUMN `website` VARCHAR(255) DEFAULT NULL',
            'facebook_url' => 'ALTER TABLE `restaurant` ADD COLUMN `facebook_url` VARCHAR(255) DEFAULT NULL',
            'instagram_url' => 'ALTER TABLE `restaurant` ADD COLUMN `instagram_url` VARCHAR(255) DEFAULT NULL',
            'tripadvisor_url' => 'ALTER TABLE `restaurant` ADD COLUMN `tripadvisor_url` VARCHAR(255) DEFAULT NULL',
            'average_price_lunch' => 'ALTER TABLE `restaurant` ADD COLUMN `average_price_lunch` DECIMAL(10,2) DEFAULT NULL',
            'average_price_dinner' => 'ALTER TABLE `restaurant` ADD COLUMN `average_price_dinner` DECIMAL(10,2) DEFAULT NULL',
            'longitude' => 'ALTER TABLE `restaurant` ADD COLUMN `longitude` VARCHAR(255) DEFAULT NULL',
            'latitude' => 'ALTER TABLE `restaurant` ADD COLUMN `latitude` VARCHAR(255) DEFAULT NULL',
            'logo_filename' => 'ALTER TABLE `restaurant` ADD COLUMN `logo_filename` VARCHAR(255) DEFAULT NULL',
            'display_opening_hours' => 'ALTER TABLE `restaurant` ADD COLUMN `display_opening_hours` TINYINT(1) DEFAULT 1'
        ];
        
        // Check each column
        $columnsToAdd = [];
        
        $stmt = $pdo->query("DESCRIBE `restaurant`");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredColumns as $column => $addSQL) {
            if (!in_array($column, $existingColumns)) {
                $columnsToAdd[$column] = $addSQL;
            }
        }
        
        if (count($columnsToAdd) > 0) {
            echo "Found " . count($columnsToAdd) . " missing columns. Adding them now...\n";
            
            foreach ($columnsToAdd as $column => $sql) {
                try {
                    $pdo->exec($sql);
                    echo "Added column '$column' to restaurant table.\n";
                } catch (PDOException $e) {
                    echo "Error adding column '$column': " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "All required columns already exist in the restaurant table.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error checking/creating restaurant table: " . $e->getMessage() . "\n";
}

echo "\n--- Database update completed! ---\n";
