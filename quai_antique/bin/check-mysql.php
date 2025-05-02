<?php
/**
 * MySQL Connectivity Check Script
 * 
 * This script tests if PHP can connect to MySQL using the PDO extension.
 */

// Load the MySQL extension configuration
$customConfig = dirname(__DIR__).'/config/php-mysql-config.ini';
if (file_exists($customConfig)) {
    echo "Loading custom MySQL configuration from: $customConfig\n";
    include_once($customConfig);
}

// Check for required extensions
echo "\nChecking for required extensions:\n";
echo "- mysqlnd: " . (extension_loaded('mysqlnd') ? "Loaded ✅" : "Not loaded ❌") . "\n";
echo "- pdo_mysql: " . (extension_loaded('pdo_mysql') ? "Loaded ✅" : "Not loaded ❌") . "\n";
echo "- mysqli: " . (extension_loaded('mysqli') ? "Loaded ✅" : "Optional ✓") . "\n";

// List all loaded extensions
echo "\nAll loaded extensions:\n";
$extensions = get_loaded_extensions();
sort($extensions);
echo implode(", ", $extensions) . "\n";

// Test MySQL connectivity
echo "\nTesting MySQL connectivity:\n";
try {
    // Database credentials
    $host = "localhost";
    $username = "root";
    $password = "MO3848seven_36";
    
    // Create connection using PDO
    $conn = new PDO("mysql:host=$host", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully ✅\n";
    
    // Get MySQL version
    $stmt = $conn->query('SELECT VERSION() as version');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Server version: " . $row['version'] . "\n";
    
    // Try to create the Symfony database
    try {
        $dbName = "sf_restaurant";
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
        echo "Database '$dbName' already exists or was created successfully ✅\n";
    } catch (PDOException $e) {
        echo "Database creation failed: " . $e->getMessage() . " ❌\n";
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . " ❌\n";
}

echo "\nPHP Information:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
