<?php

/**
 * MySQL Connection Test Script
 * 
 * This script checks if MySQL connection is working with your configured settings.
 */

// Load .env.local file manually
$envFile = __DIR__ . '/.env.local';
$databaseUrl = null;

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/DATABASE_URL="([^"]+)"/', $envContent, $matches)) {
        $databaseUrl = $matches[1];
    }
}

if (!$databaseUrl) {
    echo "Error: Could not find DATABASE_URL in .env.local\n";
    exit(1);
}

// Parse the database URL
$dbParams = parse_url($databaseUrl);

// Extract connection parameters
$dbName = ltrim($dbParams['path'], '/');
$dbName = strpos($dbName, '?') !== false ? substr($dbName, 0, strpos($dbName, '?')) : $dbName;
$dbUser = $dbParams['user'] ?? null;
$dbPass = $dbParams['pass'] ?? null;
$dbHost = $dbParams['host'] ?? null;
$dbPort = $dbParams['port'] ?? 3306;

echo "Testing MySQL Connection...\n";
echo "Host: $dbHost:$dbPort\n";
echo "User: $dbUser\n";
echo "Database: $dbName\n\n";

// Check if PDO MySQL is available
if (!extension_loaded('pdo_mysql')) {
    echo "Error: PDO MySQL extension is not loaded!\n";
    echo "Please install it with: sudo apt install php-mysql\n";
    exit(1);
}

// Try connecting to MySQL
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    // First try connecting to the server without specifying database
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "✅ Successfully connected to MySQL server\n";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    $dbExists = $stmt->fetchColumn();
    
    if ($dbExists) {
        echo "✅ Database '$dbName' exists\n";
        
        // Try connecting to the specific database
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        echo "✅ Successfully connected to the database\n";
    } else {
        echo "ℹ️ Database '$dbName' does not exist yet\n";
        echo "ℹ️ Run this command to create it: php bin/console doctrine:database:create\n";
    }
    
    echo "\n✅ MySQL connection test completed successfully\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "\nPossible solutions:\n";
        echo "1. Check if your MySQL credentials are correct in .env.local\n";
        echo "2. Make sure the MySQL server is running: sudo systemctl status mysql\n";
        echo "3. Try resetting the MySQL root password if needed\n";
    }
    
    exit(1);
}
