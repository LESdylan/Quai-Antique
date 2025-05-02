<?php

/**
 * Dynamic database configuration that selects the appropriate driver based on what's available
 */

// Check for required MySQL extensions
$hasMysqlnd = extension_loaded('mysqlnd');
$hasPdoMysql = extension_loaded('pdo_mysql');
$hasSQLite = extension_loaded('pdo_sqlite');

// Determine available database options
$mysqlAvailable = $hasMysqlnd && $hasPdoMysql;
$sqliteAvailable = $hasSQLite;

// Set database URL based on availability and environment
if ($_SERVER['APP_ENV'] === 'sqlite' || (!$mysqlAvailable && $sqliteAvailable)) {
    // Use SQLite
    $sqliteDbPath = dirname(__DIR__) . '/var/data.db';
    $sqliteDbDir = dirname($sqliteDbPath);
    
    // Create directory if it doesn't exist
    if (!is_dir($sqliteDbDir)) {
        mkdir($sqliteDbDir, 0777, true);
    }
    
    putenv("DATABASE_URL=sqlite:///$sqliteDbPath");
    echo "Using SQLite database at: $sqliteDbPath\n";
    
} elseif ($mysqlAvailable) {
    // Use MySQL (from .env or .env.local)
    echo "Using MySQL database configuration from .env file\n";
    
} else {
    // No suitable database driver found
    echo "\n=====================================================\n";
    echo "MISSING REQUIRED PHP EXTENSIONS FOR DATABASES\n";
    echo "=====================================================\n";
    
    if (!$hasMysqlnd) {
        echo "✘ The 'mysqlnd' extension is not loaded.\n";
    }
    
    if (!$hasPdoMysql) {
        echo "✘ The 'pdo_mysql' extension is not loaded.\n";
    }
    
    if (!$hasSQLite) {
        echo "✘ The 'pdo_sqlite' extension is not loaded.\n";
    }
    
    echo "\nTry running: bash bin/db-setup.sh\n";
    echo "This script will help you diagnose and fix the database setup.\n";
    echo "=====================================================\n\n";
}

return [
    'mysql_available' => $mysqlAvailable,
    'sqlite_available' => $sqliteAvailable,
];
