<?php
/**
 * Migration Fixer Script
 * 
 * This script marks problematic migrations as executed without running them
 * Use when you get errors like "Table already exists" during migrations
 */

require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

// Load environment variables from .env files
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

// Get database configuration from environment
$dbUrl = $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');
if (!$dbUrl) {
    die("Error: DATABASE_URL environment variable not found.\n");
}

// Parse database URL
$params = parse_url($dbUrl);
$dsnParams = [];

if (isset($params['query'])) {
    parse_str($params['query'], $dsnParams);
}

// Create connection parameters
$connectionParams = [
    'dbname' => ltrim($params['path'], '/'),
    'user' => $params['user'] ?? null,
    'password' => $params['pass'] ?? null,
    'host' => $params['host'] ?? 'localhost',
    'driver' => 'pdo_mysql',
];

if (isset($params['port'])) {
    $connectionParams['port'] = $params['port'];
}

try {
    // Connect to database
    $conn = DriverManager::getConnection($connectionParams);
    
    // Problematic migration versions to mark as executed
    $migrations = [
        'DoctrineMigrations\\Version20250504001756',
        'DoctrineMigrations\\Version20250504001802',
    ];
    
    echo "Starting migration fix...\n";
    
    // Get the current executed migrations
    $stmt = $conn->prepare("SELECT version FROM doctrine_migration_versions WHERE version = ?");
    
    foreach ($migrations as $version) {
        // Check if already in migration table
        $stmt->bindValue(1, $version);
        $result = $stmt->executeQuery();
        $exists = (bool) $result->fetchOne();
        
        if ($exists) {
            echo "Migration $version is already marked as executed.\n";
            continue;
        }
        
        // Add to migration table
        $now = date('Y-m-d H:i:s');
        $conn->executeStatement(
            "INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (?, ?, 0)",
            [$version, $now]
        );
        
        echo "Migration $version has been marked as executed.\n";
    }
    
    echo "\nAll specified migrations have been processed!\n";
    echo "You can now continue with development without these migration errors.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // If doctrine_migration_versions table doesn't exist yet
    if (strpos($e->getMessage(), "Table 'doctrine_migration_versions' doesn't exist") !== false) {
        echo "\nIt appears the doctrine_migration_versions table doesn't exist yet.\n";
        echo "Run this first to create the migrations table:\n";
        echo "php bin/console doctrine:migrations:sync-metadata-storage\n\n";
    }
}
