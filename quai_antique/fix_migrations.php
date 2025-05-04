<?php
/**
 * Smart Migration Status Fixer
 * 
 * This script checks which problematic migrations need to be marked as executed
 * and only inserts the ones that don't already exist in the database.
 */

// Load database config from .env or .env.local
$envFiles = [__DIR__ . '/.env.local', __DIR__ . '/.env'];
$databaseUrl = null;

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/DATABASE_URL="([^"]+)"/', $envContent, $matches)) {
            $databaseUrl = $matches[1];
            break;
        }
    }
}

if (!$databaseUrl) {
    echo "Error: Could not find DATABASE_URL in .env files\n";
    exit(1);
}

// Parse the database URL
$dbParams = parse_url($databaseUrl);
$dbName = ltrim($dbParams['path'], '/');
$dbName = strpos($dbName, '?') !== false ? substr($dbName, 0, strpos($dbName, '?')) : $dbName;
$dbUser = $dbParams['user'] ?? null;
$dbPass = $dbParams['pass'] ?? null;
$dbHost = $dbParams['host'] ?? null;
$dbPort = $dbParams['port'] ?? 3306;

echo "==============================================================\n";
echo "   Migration Status Fix Script for Quai Antique Application   \n";
echo "==============================================================\n\n";
echo "Database: $dbHost:$dbPort/$dbName\n\n";

// List of problematic migrations to fix
$migrations = [
    'DoctrineMigrations\\Version20250504001756',
    'DoctrineMigrations\\Version20250504001802',
];

try {
    // Connect to database
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "✅ Connected to the database successfully\n\n";
    
    // Check if migration table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'doctrine_migration_versions'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ Error: Migration table doesn't exist. Run migrations:sync-metadata-storage first:\n";
        echo "   php bin/console doctrine:migrations:sync-metadata-storage\n";
        exit(1);
    }
    
    // Check which migrations are already executed
    $existingMigrations = [];
    $stmt = $pdo->query("SELECT version FROM doctrine_migration_versions");
    while ($row = $stmt->fetch()) {
        $existingMigrations[] = $row['version'];
    }
    
    echo "Found migrations in database:\n";
    foreach ($existingMigrations as $migration) {
        echo "  ✓ $migration\n";
    }
    echo "\n";
    
    // Find which migrations need to be added
    $migrationsToAdd = array_diff($migrations, $existingMigrations);
    
    if (count($migrationsToAdd) === 0) {
        echo "✅ All migrations are already marked as executed. Nothing to do!\n";
    } else {
        echo "Adding " . count($migrationsToAdd) . " missing migration(s):\n";
        
        foreach ($migrationsToAdd as $migration) {
            echo "  • Adding $migration... ";
            $stmt = $pdo->prepare("INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (?, NOW(), 0)");
            $stmt->execute([$migration]);
            echo "✅ Done\n";
        }
        
        echo "\n✅ All migrations have been marked as executed!\n";
    }

    echo "\nYou can now run migrations again:\n";
    echo "php bin/console doctrine:migrations:migrate\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
