#!/usr/bin/env php
<?php
// This script adds the missing isFeatured column to the dish table

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Database connection settings
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';
$dbPass = 'MO3848seven_36';
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

// Connect to the database
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔌 Connected to database successfully\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Check if the column exists already
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM dish LIKE 'is_featured'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✅ Column 'is_featured' already exists in the dish table\n";
    } else {
        echo "🛠️ Adding 'is_featured' column to the dish table...\n";
        
        // SQL to add the column
        $sql = "ALTER TABLE dish ADD is_featured TINYINT(1) DEFAULT 0";
        
        $pdo->exec($sql);
        echo "✅ Column 'is_featured' added successfully\n";
    }
    
    echo "✨ Database update completed successfully!\n";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
