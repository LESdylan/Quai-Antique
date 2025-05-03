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

echo "\n--- Adding missing column to promotion table ---\n";

// Check if the column already exists
try {
    $checkColumn = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = 'promotion' AND COLUMN_NAME = 'image_filename'");
    $columnExists = (bool) $checkColumn->rowCount();
    
    if ($columnExists) {
        echo "Column 'image_filename' already exists in the promotion table.\n";
    } else {
        // Add the missing column
        $pdo->exec("ALTER TABLE promotion ADD COLUMN image_filename VARCHAR(255) NULL AFTER type");
        echo "Successfully added 'image_filename' column to the promotion table.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // If the error is because the table doesn't exist, suggest creating it
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "\nThe 'promotion' table doesn't exist yet. You need to create it first.\n";
        echo "Run Symfony's migration command:\n";
        echo "php bin/console doctrine:migrations:diff\n";
        echo "php bin/console doctrine:migrations:migrate\n";
    }
}

echo "\nScript completed.\n";
