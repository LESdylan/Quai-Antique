<?php
/**
 * Script to fix the menu_dish junction table
 */

// Bootstrap the application
$projectDir = dirname(__DIR__);
require_once $projectDir . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv($projectDir.'/.env');

// Get database connection details
$dbUrl = $_ENV['DATABASE_URL'] ?? null;

if (!$dbUrl) {
    echo "Error: DATABASE_URL not defined in environment variables.\n";
    exit(1);
}

// Parse database URL
$params = parse_url($dbUrl);
$dbHost = $params['host'] ?? 'localhost';
$dbPort = $params['port'] ?? 3306;
$dbName = ltrim($params['path'] ?? '', '/');
$dbUser = $params['user'] ?? null;
$dbPass = $params['pass'] ?? null;

// Handle query parameters in the database name
if (strpos($dbName, '?') !== false) {
    $dbName = substr($dbName, 0, strpos($dbName, '?'));
}

echo "Database connection info:\n";
echo "Host: $dbHost\n";
echo "Name: $dbName\n";
echo "User: $dbUser\n";

// Connect to the database
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Check if menu_dish table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'menu_dish'");
    $tableExists = (bool) $stmt->rowCount();
    
    if ($tableExists) {
        // Drop the existing table to recreate it with correct structure
        echo "Table menu_dish already exists. Dropping it to recreate with correct syntax...\n";
        $pdo->exec("DROP TABLE menu_dish");
        echo "Dropped menu_dish table.\n";
    }
    
    // Check if referenced tables exist
    $menuTableExists = false;
    $dishTableExists = false;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'menu'");
    $menuTableExists = (bool) $stmt->rowCount();
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'dish'");
    $dishTableExists = (bool) $stmt->rowCount();
    
    // Create menu_dish junction table with correct syntax
    echo "Creating menu_dish table with correct syntax...\n";
    
    $createSql = "CREATE TABLE `menu_dish` (
        `menu_id` INT NOT NULL,
        `dish_id` INT NOT NULL,
        PRIMARY KEY (`menu_id`, `dish_id`)";
    
    // Add foreign key constraints only if referenced tables exist
    if ($menuTableExists && $dishTableExists) {
        $createSql .= ",
        CONSTRAINT `FK_menu_dish_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_menu_dish_dish` FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE";
    } elseif (!$menuTableExists) {
        echo "Warning: menu table doesn't exist. Foreign key constraint for menu_id will not be added.\n";
    } elseif (!$dishTableExists) {
        echo "Warning: dish table doesn't exist. Foreign key constraint for dish_id will not be added.\n";
    }
    
    $createSql .= "
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createSql);
    echo "Successfully created menu_dish table.\n";
    
    // If foreign keys couldn't be added initially due to missing tables, suggest how to add them later
    if (!$menuTableExists || !$dishTableExists) {
        echo "\nTo add foreign key constraints later, run the following SQL commands after creating the referenced tables:\n";
        
        if (!$menuTableExists) {
            echo "ALTER TABLE `menu_dish` ADD CONSTRAINT `FK_menu_dish_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;\n";
        }
        
        if (!$dishTableExists) {
            echo "ALTER TABLE `menu_dish` ADD CONSTRAINT `FK_menu_dish_dish` FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE;\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done!\n";
