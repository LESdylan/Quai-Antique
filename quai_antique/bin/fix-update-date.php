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

echo "\n--- Step 1: Identifying Tables with update_date Column ---\n";

// Find all tables with update_date column
try {
    $tableQuery = $pdo->query("SHOW TABLES");
    $tables = $tableQuery->fetchAll(PDO::FETCH_COLUMN);
    
    $tablesWithUpdateDate = [];
    
    foreach ($tables as $table) {
        $columnsQuery = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'update_date' || $column['Field'] === 'updated_at' || $column['Field'] === 'updatedAt') {
                // Check if the column is NOT NULL and has no default value
                $isNull = strtoupper($column['Null']) === 'NO';
                $hasNoDefault = $column['Default'] === null;
                
                $tablesWithUpdateDate[] = [
                    'table' => $table,
                    'column' => $column['Field'],
                    'isNull' => $isNull,
                    'hasNoDefault' => $hasNoDefault,
                    'type' => $column['Type']
                ];
                
                echo "Found table '$table' with {$column['Field']} column (Type: {$column['Type']}, Nullable: " . ($isNull ? "NO" : "YES") . ")\n";
                break;
            }
        }
    }
    
    if (empty($tablesWithUpdateDate)) {
        echo "No tables found with update_date/updated_at columns.\n";
        exit;
    }
    
    echo "\n--- Step 2: Fixing NULL Values in Update Columns ---\n";
    
    // Fix NULL values in these tables
    foreach ($tablesWithUpdateDate as $tableInfo) {
        $table = $tableInfo['table'];
        $column = $tableInfo['column'];
        
        if ($tableInfo['isNull']) {
            echo "Table '$table' has non-nullable $column. Checking for records...\n";
            
            // Count rows with NULL values
            $countQuery = $pdo->query("SELECT COUNT(*) FROM `$table` WHERE `$column` IS NULL");
            $nullCount = $countQuery->fetchColumn();
            
            if ($nullCount > 0) {
                echo "  Found $nullCount records with NULL values in $column.\n";
                
                // Update NULL values with current timestamp
                if (strpos($tableInfo['type'], 'datetime') !== false) {
                    $pdo->exec("UPDATE `$table` SET `$column` = NOW() WHERE `$column` IS NULL");
                } else {
                    $pdo->exec("UPDATE `$table` SET `$column` = CURRENT_DATE() WHERE `$column` IS NULL");
                }
                
                echo "  Updated $nullCount records with current timestamp.\n";
            } else {
                echo "  No records found with NULL values in $column.\n";
            }
        }
    }
    
    echo "\n--- Step 3: Adding Default Values if Needed ---\n";
    
    // Add default values to columns that need them
    foreach ($tablesWithUpdateDate as $tableInfo) {
        $table = $tableInfo['table'];
        $column = $tableInfo['column'];
        
        if ($tableInfo['isNull'] && $tableInfo['hasNoDefault']) {
            echo "Column $column in table $table is NOT NULL but has no default. Adding default value...\n";
            
            if (strpos($tableInfo['type'], 'datetime') !== false) {
                $pdo->exec("ALTER TABLE `$table` MODIFY `$column` {$tableInfo['type']} NOT NULL DEFAULT CURRENT_TIMESTAMP");
            } else {
                $pdo->exec("ALTER TABLE `$table` MODIFY `$column` {$tableInfo['type']} NOT NULL DEFAULT CURRENT_DATE()");
            }
            
            echo "Added default value to $column in $table.\n";
        }
    }
    
    echo "\nAll done! The update_date/updated_at columns have been fixed.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
