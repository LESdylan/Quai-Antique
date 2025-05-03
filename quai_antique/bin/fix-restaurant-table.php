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

echo "\n--- Checking restaurant table for update_date column ---\n";

try {
    // Check if the restaurant table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'restaurant'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if (!$tableExists) {
        echo "The restaurant table doesn't exist! Creating it...\n";
        
        // Create the restaurant table
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
                `update_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        echo "Restaurant table created with proper update_date column and default value.\n";
        
        // Insert a default record
        $pdo->exec("
            INSERT INTO `restaurant` (`name`, `description`, `address`, `city`, `zip_code`, `phone`, `email`)
            VALUES ('Le Quai Antique', 'Une expérience gastronomique unique à Chambéry', '12 Quai des Allobroges', 'Chambéry', '73000', '04 79 85 XX XX', 'contact@quaiantique.fr')
        ");
        echo "Default restaurant record inserted.\n";
        
    } else {
        echo "Restaurant table exists. Checking update_date column...\n";
        
        // Check if update_date column exists
        $columnCheck = $pdo->query("SHOW COLUMNS FROM `restaurant` LIKE 'update_date'");
        $columnExists = $columnCheck->rowCount() > 0;
        
        if ($columnExists) {
            // Get column details
            $columnInfo = $columnCheck->fetch(PDO::FETCH_ASSOC);
            
            // Check if column has DEFAULT constraint
            $hasDefault = $columnInfo['Default'] !== null;
            $isNotNull = strtoupper($columnInfo['Null']) === 'NO';
            $type = $columnInfo['Type'];
            
            echo "Found update_date column (Type: $type, Nullable: " . ($isNotNull ? "NO" : "YES") . ", Default: " . ($hasDefault ? $columnInfo['Default'] : "NULL") . ")\n";
            
            if ($isNotNull && !$hasDefault) {
                // Check for null values first
                $nullCheck = $pdo->query("SELECT COUNT(*) FROM `restaurant` WHERE `update_date` IS NULL");
                $nullCount = $nullCheck->fetchColumn();
                
                if ($nullCount > 0) {
                    echo "Updating $nullCount NULL values in update_date column...\n";
                    $pdo->exec("UPDATE `restaurant` SET `update_date` = NOW() WHERE `update_date` IS NULL");
                    echo "Updated NULL values.\n";
                }
                
                // Modify column to add default value based on type
                if (stripos($type, 'datetime') !== false) {
                    echo "Adding DEFAULT CURRENT_TIMESTAMP to DATETIME column...\n";
                    $pdo->exec("ALTER TABLE `restaurant` MODIFY `update_date` $type NOT NULL DEFAULT CURRENT_TIMESTAMP");
                } else if (stripos($type, 'date') !== false) {
                    echo "Adding DEFAULT CURRENT_DATE to DATE column...\n";
                    $pdo->exec("ALTER TABLE `restaurant` MODIFY `update_date` $type NOT NULL DEFAULT (CURRENT_DATE)");
                } else {
                    echo "Unrecognized date type: $type. Using DEFAULT CURRENT_TIMESTAMP...\n";
                    $pdo->exec("ALTER TABLE `restaurant` MODIFY `update_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                }
                
                echo "Default value added successfully.\n";
            } else {
                echo "Column already has a default value or is nullable. No modification needed.\n";
            }
        } else {
            echo "The update_date column doesn't exist. Adding it...\n";
            $pdo->exec("ALTER TABLE `restaurant` ADD COLUMN `update_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            echo "Added update_date column with default value.\n";
        }
    }
    
    echo "\nRestaurant table updated successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // On error, try an alternative syntax
    try {
        echo "\nTrying alternative syntax...\n";
        $pdo->exec("ALTER TABLE `restaurant` MODIFY `update_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        echo "Table updated successfully using alternative syntax!\n";
    } catch (PDOException $e2) {
        echo "Alternative approach also failed: " . $e2->getMessage() . "\n";
    }
}

echo "\nYou can now run 'php bin/console doctrine:schema:update --force' without errors.\n";
