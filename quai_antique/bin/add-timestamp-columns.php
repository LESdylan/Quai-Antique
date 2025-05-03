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

echo "\n--- Step 1: Checking tables for timestamp columns ---\n";

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Define timestamp columns to check for
$timestampColumns = [
    'created_at' => 'DATETIME',
    'updated_at' => 'DATETIME',
    'create_date' => 'DATETIME',
    'update_date' => 'DATETIME'
];

foreach ($tables as $table) {
    echo "\nChecking table: $table\n";
    
    // Get columns for this table
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Check for timestamp columns
    $missingColumns = [];
    foreach ($timestampColumns as $column => $type) {
        if (!in_array($column, $columnNames)) {
            $missingColumns[$column] = $type;
        }
    }
    
    // Add missing columns
    if (!empty($missingColumns)) {
        echo "  Table $table is missing timestamp columns: " . implode(', ', array_keys($missingColumns)) . "\n";
        
        foreach ($missingColumns as $column => $type) {
            try {
                $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $type";
                
                // Set default values
                if ($column === 'created_at' || $column === 'create_date') {
                    $sql .= " DEFAULT CURRENT_TIMESTAMP"; 
                } else {
                    $sql .= " DEFAULT NULL";
                }
                
                $pdo->exec($sql);
                echo "  Added column $column to table $table\n";
                
                // Update existing rows 
                if ($column === 'created_at' || $column === 'create_date') {
                    $pdo->exec("UPDATE `$table` SET `$column` = NOW() WHERE `$column` IS NULL");
                    echo "  Updated NULL values in $column to current timestamp\n";
                }
            } catch (PDOException $e) {
                echo "  Failed to add column $column: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "  Table $table has all necessary timestamp columns\n";
    }
}

echo "\n--- Finished! ---\n";
echo "All tables have been checked and updated as needed.\n";
