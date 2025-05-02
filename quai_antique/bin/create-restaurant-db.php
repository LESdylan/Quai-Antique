<?php
/**
 * Quai Antique Restaurant - Database Tables Creator
 * 
 * This script creates the exact tables specified for the restaurant application:
 * - User
 * - Restaurant
 * - Picture
 * - Booking
 * - Menu
 * - Category
 * - Food
 * - Menu_Category
 * - Food_Category
 * 
 * Usage: php bin/create-restaurant-db.php [--force]
 */

// Execute shell command and return output
function executeCommand($command) {
    echo "Running: $command\n";
    $output = [];
    exec($command, $output, $returnCode);
    echo implode("\n", $output) . "\n";
    
    if ($returnCode !== 0) {
        echo "Command failed with code $returnCode\n";
    }
    return $returnCode === 0;
}

// Create SQL file with table definitions
function createSQLFile($path) {
    $sql = <<<SQL
-- Restaurant database tables

-- User table for authentication and user management
CREATE TABLE `user` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(64) DEFAULT NULL,
    last_name VARCHAR(64) DEFAULT NULL,
    roles JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurant information table
CREATE TABLE `restaurant` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(180) DEFAULT NULL,
    opening_hours TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Picture table for image gallery
CREATE TABLE `picture` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(64) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    position INT DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking table for table reservations
CREATE TABLE `booking` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    date DATETIME NOT NULL,
    guest_count INT NOT NULL,
    last_name VARCHAR(64) NOT NULL,
    first_name VARCHAR(64) DEFAULT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    notes TEXT DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu table for restaurant menus
CREATE TABLE `menu` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Category table for food categories
CREATE TABLE `category` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    position INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food table for menu items
CREATE TABLE `food` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu_Category join table for menu-category relationships
CREATE TABLE `menu_category` (
    menu_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (menu_id, category_id),
    CONSTRAINT fk_menu_category_menu FOREIGN KEY (menu_id) REFERENCES `menu` (id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_category_category FOREIGN KEY (category_id) REFERENCES `category` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food_Category join table for food-category relationships
CREATE TABLE `food_category` (
    food_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (food_id, category_id),
    CONSTRAINT fk_food_category_food FOREIGN KEY (food_id) REFERENCES `food` (id) ON DELETE CASCADE,
    CONSTRAINT fk_food_category_category FOREIGN KEY (category_id) REFERENCES `category` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurant table (for real tables in the restaurant)
CREATE TABLE `restaurant_table` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number INT NOT NULL,
    seats INT NOT NULL,
    location VARCHAR(64) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking_Table join table for booking-table relationships
CREATE TABLE `booking_table` (
    booking_id INT NOT NULL,
    table_id INT NOT NULL,
    PRIMARY KEY (booking_id, table_id),
    CONSTRAINT fk_booking_table_booking FOREIGN KEY (booking_id) REFERENCES `booking` (id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_table_table FOREIGN KEY (table_id) REFERENCES `restaurant_table` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    file_put_contents($path, $sql);
    echo "Created SQL file: $path\n";
    return true;
}

// Bootstrap the application
$projectDir = dirname(__DIR__);
echo "======================================================\n";
echo "Quai Antique Restaurant - Table Creator\n";
echo "======================================================\n";

// Check for force option
$force = in_array('--force', $argv);

// 1. Prepare the database
if ($force) {
    echo "\n[Step 1] Dropping and recreating database\n";
    executeCommand("php $projectDir/bin/console doctrine:database:drop --force --if-exists");
    executeCommand("php $projectDir/bin/console doctrine:database:create --if-not-exists");
} else {
    echo "\n[Step 1] Ensuring database exists\n";
    executeCommand("php $projectDir/bin/console doctrine:database:create --if-not-exists");
}

// 2. Create SQL file
echo "\n[Step 2] Creating SQL file\n";
$sqlFile = "$projectDir/database/restaurant-schema.sql";
createSQLFile($sqlFile);

// 3. Import SQL file using our dedicated import script
echo "\n[Step 3] Importing SQL into database\n";
if (file_exists("$projectDir/bin/import-sql.php")) {
    $importResult = executeCommand("php $projectDir/bin/import-sql.php $sqlFile");
    if (!$importResult) {
        echo "WARNING: There were issues during SQL import. The database might be incomplete.\n";
        echo "Please check the tables manually with: php bin/console doctrine:query:sql 'SHOW TABLES'\n";
        echo "You may need to retry the import or fix the SQL errors.\n";
    }
} else {
    echo "Error: import-sql.php script not found. Please ensure it exists.\n";
    exit(1);
}

echo "\n[Step 4] Database setup complete\n";
echo "The following tables have been created:\n";
echo "- User\n";
echo "- Restaurant\n";
echo "- Picture\n";
echo "- Booking\n";
echo "- Menu\n";
echo "- Category\n";
echo "- Food\n";
echo "- Menu_Category\n";
echo "- Food_Category\n";
echo "- Restaurant_Table (to avoid conflict with SQL keyword 'TABLE')\n";
echo "- Booking_Table\n";

echo "\nTo view the tables, use: php bin/console doctrine:query:sql 'SHOW TABLES'\n";
