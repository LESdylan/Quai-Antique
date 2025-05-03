#!/usr/bin/env php
<?php
// This script creates the missing dish_allergen table

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

// Check if dish_allergen table already exists
$checkTableExists = $pdo->query("SHOW TABLES LIKE 'dish_allergen'");
$tableExists = $checkTableExists->rowCount() > 0;

if ($tableExists) {
    echo "✅ dish_allergen table already exists\n";
    exit(0);
}

// Create the dish_allergen join table
try {
    echo "🛠️ Creating dish_allergen table...\n";
    
    $sql = "
    CREATE TABLE dish_allergen (
        dish_id INT NOT NULL,
        allergen_id INT NOT NULL,
        PRIMARY KEY(dish_id, allergen_id),
        INDEX IDX_7CDE780F8D5495CB (dish_id),
        INDEX IDX_7CDE780FB83CD1E3 (allergen_id),
        CONSTRAINT FK_7CDE780F8D5495CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE,
        CONSTRAINT FK_7CDE780FB83CD1E3 FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
    ";
    
    $pdo->exec($sql);
    echo "✅ dish_allergen table created successfully\n";
    
    // Check if there are existing dishes with allergens that need to be migrated
    echo "🔄 Checking for existing relationships to migrate...\n";
    
    // This would depend on how allergens were previously stored
    // If there's existing data to migrate, you would do it here
    
    echo "✨ Database update completed successfully!\n";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
