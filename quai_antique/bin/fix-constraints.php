#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings with hardcoded credentials
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';  // Using the provided username
$dbPass = 'MO3848seven_36';  // Using the provided password
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

echo "Connecting to database: $dbName on $dbHost...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Function to check if a table exists
function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check if a column exists in a table
function columnExists($pdo, $tableName, $columnName) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check if a foreign key exists and drop it safely
function safelyDropForeignKey($pdo, $table, $constraintName) {
    try {
        echo "Checking for foreign key '$constraintName' in table '$table'...\n";
        
        // Check if the table exists first
        if (!tableExists($pdo, $table)) {
            echo "Table '$table' does not exist!\n";
            return false;
        }
        
        // Find foreign key constraints matching the name
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $stmt->execute([$table, $constraintName]);
        $exists = (bool)$stmt->fetchColumn();
        
        if ($exists) {
            echo "Foreign key '$constraintName' exists in '$table'. Dropping it...\n";
            $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
            echo "Foreign key dropped successfully.\n";
            return true;
        } else {
            echo "Foreign key '$constraintName' does not exist in '$table'. No action needed.\n";
            return false;
        }
    } catch (PDOException $e) {
        echo "Error while processing foreign key '$constraintName' in table '$table': " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to find and drop all foreign keys in a table
function dropAllForeignKeys($pdo, $table) {
    try {
        // Check if the table exists first
        if (!tableExists($pdo, $table)) {
            echo "Table '$table' does not exist! Skipping.\n";
            return false;
        }
        
        echo "Finding all foreign keys in '$table'...\n";
        
        $stmt = $pdo->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $stmt->execute([$table]);
        $constraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($constraints) === 0) {
            echo "No foreign keys found in '$table'.\n";
            return true;
        }
        
        echo "Found " . count($constraints) . " foreign key(s) in '$table'. Dropping them...\n";
        
        foreach ($constraints as $constraintName) {
            $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
            echo "Dropped foreign key '$constraintName' from '$table'.\n";
        }
        
        echo "All foreign keys dropped from '$table'.\n";
        return true;
    } catch (PDOException $e) {
        echo "Error dropping foreign keys from '$table': " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to recreate a join table with proper constraints
function recreateMenuDishTable($pdo) {
    try {
        echo "Recreating menu_dish join table...\n";
        
        // Check if menu and dish tables exist
        if (!tableExists($pdo, 'menu') || !tableExists($pdo, 'dish')) {
            echo "Error: menu or dish table doesn't exist! Cannot recreate join table.\n";
            return false;
        }
        
        // Check if menu_dish table exists
        if (tableExists($pdo, 'menu_dish')) {
            // First drop any existing foreign keys
            dropAllForeignKeys($pdo, 'menu_dish');
            
            // Drop the table
            $pdo->exec("DROP TABLE IF EXISTS `menu_dish`");
            echo "Dropped existing menu_dish table.\n";
        }
        
        // Create a new menu_dish table with proper constraints
        $pdo->exec("
            CREATE TABLE `menu_dish` (
                `menu_id` INT NOT NULL,
                `dish_id` INT NOT NULL,
                PRIMARY KEY(`menu_id`, `dish_id`),
                INDEX IDX_5D327CF6CCD7E912 (`menu_id`),
                INDEX IDX_5D327CF6148EB0CB (`dish_id`),
                CONSTRAINT FK_5D327CF6CCD7E912 FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
                CONSTRAINT FK_5D327CF6148EB0CB FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        
        echo "Successfully recreated menu_dish table with proper constraints.\n";
        return true;
    } catch (PDOException $e) {
        echo "Error recreating menu_dish table: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "\n--- Step 1: Handling problematic foreign keys ---\n";

// Foreign keys to check and potentially drop
$foreignKeys = [
    ['image', 'fk_image_dish'],
    ['image', 'fk_image_dish_id'],
    ['dish', 'fk_dish_category'],
    ['dish', 'fk_dish_category_id'],
    ['menu_dish', 'FK_menu_dish_dish_id'],
    ['menu_dish', 'FK_menu_dish_menu_id'],
    ['menu_dish', 'FK_menu_dish_dish']
];

// Check and drop each foreign key
foreach ($foreignKeys as [$table, $constraint]) {
    safelyDropForeignKey($pdo, $table, $constraint);
}

echo "\n--- Step 2: Recreating menu_dish join table ---\n";
recreateMenuDishTable($pdo);

echo "\n--- Step 3: Adding constraints for image and dish relationships ---\n";

try {
    // Check if 'dish_id' column exists in image table
    if (columnExists($pdo, 'image', 'dish_id')) {
        // Check if constraint already exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'image' 
            AND CONSTRAINT_NAME = 'FK_C53D045F148EB0CB'
        ");
        $stmt->execute();
        $constraintExists = (bool)$stmt->fetchColumn();
        
        if ($constraintExists) {
            echo "Constraint FK_C53D045F148EB0CB already exists on image.dish_id. Skipping.\n";
        } else {
            // Add proper constraint for image->dish relationship
            echo "Adding proper foreign key constraint on image.dish_id...\n";
            $pdo->exec("
                ALTER TABLE `image`
                ADD CONSTRAINT `FK_C53D045F148EB0CB` 
                FOREIGN KEY (`dish_id`) 
                REFERENCES `dish` (`id`)
                ON DELETE SET NULL
            ");
            echo "Constraint added successfully.\n";
        }
    } else {
        echo "Column 'dish_id' not found in 'image' table. Cannot add constraint.\n";
    }
    
    // Check if 'category_id' column exists in dish table
    if (columnExists($pdo, 'dish', 'category_id')) {
        // Check if constraint already exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dish' 
            AND CONSTRAINT_NAME = 'FK_957D8CB812469DE2'
        ");
        $stmt->execute();
        $constraintExists = (bool)$stmt->fetchColumn();
        
        if ($constraintExists) {
            echo "Constraint FK_957D8CB812469DE2 already exists on dish.category_id. Skipping.\n";
        } else {
            // Add proper constraint for dish->category relationship
            echo "Adding proper foreign key constraint on dish.category_id...\n";
            $pdo->exec("
                ALTER TABLE `dish`
                ADD CONSTRAINT `FK_957D8CB812469DE2` 
                FOREIGN KEY (`category_id`) 
                REFERENCES `category` (`id`)
                ON DELETE SET NULL
            ");
            echo "Constraint added successfully.\n";
        }
    } else {
        echo "Column 'category_id' not found in 'dish' table. Cannot add constraint.\n";
    }
    
    echo "\nAll constraints have been processed.\n";
    echo "You should now be able to run doctrine:schema:update --force without errors.\n";
    
} catch (PDOException $e) {
    echo "Error adding constraints: " . $e->getMessage() . "\n";
}
