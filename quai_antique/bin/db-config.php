<?php
/**
 * Dynamic database configuration for Symfony
 * This script checks if PDO MySQL is available and sets DATABASE_URL accordingly
 */

// Function to check if PDO MySQL is available
function pdo_mysql_available() {
    // Check if PDO is available
    if (!class_exists('PDO')) {
        return false;
    }
    
    // Check if PDO MySQL driver is available
    $drivers = PDO::getAvailableDrivers();
    return in_array('mysql', $drivers);
}

// Load environment variables
$envFile = __DIR__ . '/../.env.dynamic';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    preg_match('/MYSQL_URL="([^"]+)"/', $envContent, $mysqlMatches);
    preg_match('/SQLITE_URL="([^"]+)"/', $envContent, $sqliteMatches);
    
    $mysqlUrl = $mysqlMatches[1] ?? null;
    $sqliteUrl = $sqliteMatches[1] ?? null;
}

// Set the appropriate DATABASE_URL
$databaseUrl = pdo_mysql_available() && isset($mysqlUrl) ? $mysqlUrl : $sqliteUrl;

// Output the configuration
echo "DATABASE_URL=\"$databaseUrl\"\n";

// Exit with success code
exit(0);
