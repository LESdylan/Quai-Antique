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

// Function to safely drop foreign keys if they exist
function safelyDropForeignKey($pdo, $table, $constraintName) {
    try {
        // Check if the foreign key exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
        ");
        $stmt->execute([$table, $constraintName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "Dropping foreign key: $constraintName from table $table\n";
            $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
            return true;
        } else {
            echo "Foreign key $constraintName does not exist in table $table\n";
            return false;
        }
    } catch (PDOException $e) {
        echo "Error checking/dropping foreign key: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to safely create a table if it doesn't exist
function safelyCreateTable($pdo, $tableName, $createSql) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "Creating table: $tableName\n";
            $pdo->exec($createSql);
            return true;
        } else {
            echo "Table $tableName already exists\n";
            return false;
        }
    } catch (PDOException $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
        return false;
    }
}

// Step 1: Drop problematic foreign keys
$foreignKeysToCheck = [
    ['image', 'fk_image_dish'],
    ['image', 'fk_image_dish_id'],
    ['dish', 'fk_dish_category'],
    ['dish', 'fk_dish_category_id'],
    ['reservation', 'FK_reservation_user'],
    ['reservation', 'fk_reservation_user_id'],
    ['menu_dish', 'FK_menu_dish_dish_id'],
    ['menu_dish', 'FK_menu_dish_menu_id'],
    ['menu_dish', 'FK_menu_dish_dish']
];

echo "\n--- Step 1: Checking and dropping foreign keys ---\n";
foreach ($foreignKeysToCheck as [$table, $constraint]) {
    safelyDropForeignKey($pdo, $table, $constraint);
}

// Step 2: Create missing join table for dish-allergen relationship
echo "\n--- Step 2: Creating missing tables ---\n";

safelyCreateTable($pdo, 'dish_allergen', "
    CREATE TABLE dish_allergen (
        dish_id INT NOT NULL,
        allergen_id INT NOT NULL,
        PRIMARY KEY(dish_id, allergen_id),
        INDEX IDX_3C4389A5148EB0CB (dish_id),
        INDEX IDX_3C4389A56E775A4A (allergen_id),
        CONSTRAINT FK_3C4389A5148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE,
        CONSTRAINT FK_3C4389A56E775A4A FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

safelyCreateTable($pdo, 'tag', "
    CREATE TABLE tag (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(64) NOT NULL,
        slug VARCHAR(64) NOT NULL,
        color VARCHAR(7) DEFAULT NULL,
        PRIMARY KEY(id),
        UNIQUE KEY(slug)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

safelyCreateTable($pdo, 'image_tag', "
    CREATE TABLE image_tag (
        image_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY(image_id, tag_id),
        INDEX IDX_5B6367D03DA5256D (image_id),
        INDEX IDX_5B6367D0BAD26311 (tag_id),
        CONSTRAINT FK_5B6367D03DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE,
        CONSTRAINT FK_5B6367D0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

// Step 3: Create new entity tables
echo "\n--- Step 3: Creating new entity tables ---\n";

safelyCreateTable($pdo, 'message', "
    CREATE TABLE message (
        id INT AUTO_INCREMENT NOT NULL, 
        name VARCHAR(255) NOT NULL, 
        email VARCHAR(255) NOT NULL, 
        subject VARCHAR(255) NOT NULL, 
        message LONGTEXT NOT NULL, 
        is_read TINYINT(1) NOT NULL, 
        created_at DATETIME NOT NULL, 
        read_at DATETIME DEFAULT NULL, 
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

safelyCreateTable($pdo, 'page', "
    CREATE TABLE page (
        id INT AUTO_INCREMENT NOT NULL, 
        title VARCHAR(255) NOT NULL, 
        slug VARCHAR(255) NOT NULL, 
        content LONGTEXT NOT NULL, 
        is_published TINYINT(1) NOT NULL, 
        created_at DATETIME NOT NULL, 
        updated_at DATETIME NOT NULL, 
        meta_title VARCHAR(255) DEFAULT NULL, 
        meta_description LONGTEXT DEFAULT NULL, 
        UNIQUE INDEX UNIQ_140AB620989D9B62 (slug), 
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

safelyCreateTable($pdo, 'promotion', "
    CREATE TABLE promotion (
        id INT AUTO_INCREMENT NOT NULL, 
        title VARCHAR(255) NOT NULL, 
        description LONGTEXT DEFAULT NULL, 
        type VARCHAR(20) NOT NULL, 
        start_date DATE NOT NULL, 
        end_date DATE NOT NULL, 
        is_active TINYINT(1) NOT NULL, 
        position INT NOT NULL, 
        button_text VARCHAR(50) DEFAULT NULL, 
        button_link VARCHAR(255) DEFAULT NULL, 
        created_at DATETIME NOT NULL, 
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

safelyCreateTable($pdo, 'reservation_table', "
    CREATE TABLE reservation_table (
        reservation_id INT NOT NULL, 
        table_id INT NOT NULL, 
        INDEX IDX_B5565FE1B83297E7 (reservation_id), 
        INDEX IDX_B5565FE1ECFF285C (table_id), 
        PRIMARY KEY(reservation_id, table_id),
        CONSTRAINT FK_B5565FE1B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE,
        CONSTRAINT FK_B5565FE1ECFF285C FOREIGN KEY (table_id) REFERENCES restaurant_table (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
");

echo "\n--- Step 4: Add missing columns to image table ---\n";

// Function to safely add columns to a table
function safelyAddColumn($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            echo "Adding column $column to table $table\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            return true;
        } else {
            echo "Column $column already exists in table $table\n";
            return false;
        }
    } catch (PDOException $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
        return false;
    }
}

$columnsToAdd = [
    ['image', 'focal_point', 'VARCHAR(255) DEFAULT \'50% 50%\''],
    ['image', 'file_size', 'INT DEFAULT NULL'],
    ['image', 'dimensions', 'VARCHAR(255) DEFAULT NULL']
];

foreach ($columnsToAdd as [$table, $column, $definition]) {
    safelyAddColumn($pdo, $table, $column, $definition);
}

echo "\n--- Database schema update completed successfully! ---\n";
echo "You can now run 'php bin/console doctrine:schema:update --force' to apply remaining changes.\n";
