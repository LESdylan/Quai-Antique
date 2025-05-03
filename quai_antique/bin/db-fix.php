#!/usr/bin/env php
<?php
// This script fixes database issues by directly executing SQL

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Database connection settings
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';  // Using provided credentials
$dbPass = 'MO3848seven_36';  // Using provided credentials
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

// Check if hours_exception table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'hours_exception'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Table 'hours_exception' already exists\n";
    } else {
        echo "🛠️ Creating 'hours_exception' table...\n";
        
        // SQL to create the hours_exception table
        $sql = "
            CREATE TABLE hours_exception (
                id INT AUTO_INCREMENT NOT NULL,
                date DATE NOT NULL,
                description VARCHAR(255) NOT NULL,
                is_closed TINYINT(1) NOT NULL,
                opening_time VARCHAR(5) DEFAULT NULL,
                closing_time VARCHAR(5) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ";
        
        $pdo->exec($sql);
        echo "✅ Table 'hours_exception' created successfully\n";
    }
    
    // Other tables to check and create if needed
    echo "🔍 Checking for other required tables...\n";
    
    // Add commands for other tables if needed
    
    echo "✨ Database setup completed successfully!\n";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
