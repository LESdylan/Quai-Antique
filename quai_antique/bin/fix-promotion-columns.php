#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings with hardcoded credentials from your input
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';  // Using the provided username
$dbPass = 'MO3848seven_36';  // Using the provided password
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

// 1. Fix promotion table by adding any missing columns
echo "\nChecking promotion table...\n";
$columns = [
    'image_filename' => "ALTER TABLE promotion ADD COLUMN image_filename VARCHAR(255) NULL AFTER type",
    'type' => "ALTER TABLE promotion ADD COLUMN type VARCHAR(32) NOT NULL DEFAULT 'banner' AFTER button_link",
    'button_text' => "ALTER TABLE promotion ADD COLUMN button_text VARCHAR(255) NULL AFTER end_date",
    'button_link' => "ALTER TABLE promotion ADD COLUMN button_link VARCHAR(255) NULL AFTER button_text",
];

try {
    // First check if the promotion table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'promotion'")->fetchAll();
    if (count($tables) === 0) {
        echo "The promotion table does not exist. Creating it...\n";
        // Create the promotion table
        $pdo->exec("
            CREATE TABLE promotion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                button_text VARCHAR(255) NULL,
                button_link VARCHAR(255) NULL,
                type VARCHAR(32) NOT NULL DEFAULT 'banner',
                image_filename VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "Promotion table created successfully!\n";
    } else {
        // Table exists, check columns
        foreach ($columns as $column => $sql) {
            try {
                $checkColumn = $pdo->query("SHOW COLUMNS FROM promotion LIKE '$column'");
                if ($checkColumn->rowCount() == 0) {
                    echo "Adding missing column '$column' to promotion table...\n";
                    $pdo->exec($sql);
                    echo "Column '$column' added successfully.\n";
                } else {
                    echo "Column '$column' already exists.\n";
                }
            } catch (PDOException $e) {
                echo "Error with column $column: " . $e->getMessage() . "\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 2. Clean up the problematic migration from the future
echo "\nChecking for problematic migration...\n";
try {
    // First check if the doctrine_migration_versions table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'doctrine_migration_versions'")->fetchAll();
    if (count($tables) > 0) {
        // Check for the specific migration
        $stmt = $pdo->prepare("SELECT * FROM doctrine_migration_versions WHERE version = ?");
        $stmt->execute(['DoctrineMigrations\\Version20250503121200']);
        
        if ($stmt->rowCount() > 0) {
            // Delete the problematic migration
            $stmt = $pdo->prepare("DELETE FROM doctrine_migration_versions WHERE version = ?");
            $stmt->execute(['DoctrineMigrations\\Version20250503121200']);
            echo "Successfully removed problematic migration Version20250503121200\n";
        } else {
            echo "Migration Version20250503121200 not found in the database\n";
        }
    } else {
        echo "The doctrine_migration_versions table does not exist. No migrations to clean up.\n";
    }
} catch (PDOException $e) {
    echo "Error checking migrations: " . $e->getMessage() . "\n";
}

echo "\nScript completed successfully!\n";
