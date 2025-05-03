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

echo "\n--- Fixing NULL update_date values in restaurant table ---\n";

// Update all NULL values in update_date column
try {
    $stmt = $pdo->prepare("UPDATE restaurant SET update_date = NOW() WHERE update_date IS NULL");
    $affected = $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Updated $count rows with NULL update_date values.\n";
    
    // Double check if any NULL values still exist
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM restaurant WHERE update_date IS NULL");
    $nullCount = $checkStmt->fetchColumn();
    
    if ($nullCount > 0) {
        echo "Warning: There are still $nullCount rows with NULL update_date values!\n";
    } else {
        echo "All rows now have valid update_date values.\n";
    }
    
} catch (PDOException $e) {
    echo "Error updating NULL values: " . $e->getMessage() . "\n";
}

echo "\nScript completed.\n";
