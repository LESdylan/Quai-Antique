#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings with hardcoded credentials
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';
$dbPass = 'MO3848seven_36';
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

echo "\n--- Step 1: Temporarily disabling foreign key checks ---\n";
$pdo->exec("SET foreign_key_checks = 0");
echo "Foreign key checks disabled.\n";

echo "\n--- Step 2: Fixing problematic constraints ---\n";

try {
    // Check if the dish and category tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'dish'");
    $dishTableExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'category'");
    $categoryTableExists = $stmt->rowCount() > 0;
    
    if (!$dishTableExists || !$categoryTableExists) {
        echo "Error: dish or category table doesn't exist!\n";
    } else {
        // First try to drop the constraint if it exists (although the error says it doesn't)
        try {
            $pdo->exec("ALTER TABLE `dish` DROP FOREIGN KEY `FK_957D8CB812469DE2`");
            echo "Foreign key FK_957D8CB812469DE2 dropped (although it was reported as missing).\n";
        } catch (PDOException $e) {
            echo "Could not drop constraint (probably doesn't exist): " . $e->getMessage() . "\n";
        }
        
        // Now, regardless of whether the drop succeeded, add the constraint with the correct name
        try {
            // First check if the category_id column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM `dish` LIKE 'category_id'");
            $categoryIdColumnExists = $stmt->rowCount() > 0;
            
            if (!$categoryIdColumnExists) {
                echo "Error: category_id column doesn't exist in dish table!\n";
            } else {
                // Make sure there's an index on the column first
                try {
                    $pdo->exec("CREATE INDEX IDX_957D8CB812469DE2 ON dish (category_id)");
                    echo "Created index on dish.category_id.\n";
                } catch (PDOException $e) {
                    echo "Index might already exist: " . $e->getMessage() . "\n";
                }
                
                // Now add the foreign key
                $pdo->exec("
                    ALTER TABLE `dish`
                    ADD CONSTRAINT `FK_957D8CB812469DE2` 
                    FOREIGN KEY (`category_id`) 
                    REFERENCES `category` (`id`)
                    ON DELETE SET NULL
                ");
                echo "Successfully added FK_957D8CB812469DE2 constraint.\n";
            }
        } catch (PDOException $e) {
            echo "Error adding constraint: " . $e->getMessage() . "\n";
        }
        
        // Handle the dish_allergen many-to-many relationship table
        try {
            if (!tableExists($pdo, 'dish_allergen')) {
                echo "Creating dish_allergen join table...\n";
                $pdo->exec("
                    CREATE TABLE `dish_allergen` (
                        `dish_id` INT NOT NULL,
                        `allergen_id` INT NOT NULL,
                        PRIMARY KEY(`dish_id`, `allergen_id`),
                        INDEX IDX_3C4389A5148EB0CB (`dish_id`),
                        INDEX IDX_3C4389A56E775A4A (`allergen_id`),
                        CONSTRAINT FK_3C4389A5148EB0CB FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE CASCADE,
                        CONSTRAINT FK_3C4389A56E775A4A FOREIGN KEY (`allergen_id`) REFERENCES `allergen` (`id`) ON DELETE CASCADE
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                ");
                echo "dish_allergen table created.\n";
            }
        } catch (PDOException $e) {
            echo "Error handling dish_allergen table: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error fixing constraints: " . $e->getMessage() . "\n";
}

// NEW CODE: Handle reservation-user relationship
try {
    // Check if reservation and user tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'reservation'");
    $reservationTableExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    $userTableExists = $stmt->rowCount() > 0;
    
    if (!$reservationTableExists || !$userTableExists) {
        echo "Error: reservation or user table doesn't exist!\n";
    } else {
        echo "Working on reservation-user relationship...\n";
        
        // First try to drop the constraint if it exists (although the error says it doesn't)
        try {
            $pdo->exec("ALTER TABLE `reservation` DROP FOREIGN KEY `fk_reservation_user_id`");
            echo "Foreign key fk_reservation_user_id dropped.\n";
        } catch (PDOException $e) {
            echo "Could not drop constraint (probably doesn't exist): " . $e->getMessage() . "\n";
        }
        
        // Also try to drop any constraint that might reference user_id
        try {
            $stmt = $pdo->prepare("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'reservation' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME = 'user'
            ");
            $stmt->execute();
            $existingConstraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($existingConstraints as $constraint) {
                echo "Found existing constraint: $constraint - Dropping it...\n";
                $pdo->exec("ALTER TABLE `reservation` DROP FOREIGN KEY `$constraint`");
            }
        } catch (PDOException $e) {
            echo "Error finding constraints: " . $e->getMessage() . "\n";
        }
        
        // Now check if the user_id column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'user_id'");
        $userIdColumnExists = $stmt->rowCount() > 0;
        
        if (!$userIdColumnExists) {
            echo "Error: user_id column doesn't exist in reservation table!\n";
        } else {
            // Make sure there's an index on the column first
            try {
                $pdo->exec("CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)");
                echo "Created index on reservation.user_id.\n";
            } catch (PDOException $e) {
                echo "Index might already exist: " . $e->getMessage() . "\n";
            }
            
            // Now add the foreign key with the exact name Doctrine expects
            try {
                $pdo->exec("
                    ALTER TABLE `reservation`
                    ADD CONSTRAINT `FK_42C84955A76ED395` 
                    FOREIGN KEY (`user_id`) 
                    REFERENCES `user` (`id`)
                    ON DELETE SET NULL
                ");
                echo "Successfully added FK_42C84955A76ED395 (reservation.user_id -> user.id) constraint.\n";
                
                // Also add the old constraint name to ensure backward compatibility
                try {
                    $pdo->exec("
                        ALTER TABLE `reservation`
                        ADD CONSTRAINT `fk_reservation_user_id` 
                        FOREIGN KEY (`user_id`) 
                        REFERENCES `user` (`id`)
                        ON DELETE SET NULL
                    ");
                    echo "Successfully added fk_reservation_user_id constraint for backward compatibility.\n";
                } catch (PDOException $e) {
                    echo "Could not add backward compatibility constraint: " . $e->getMessage() . "\n";
                }
                
            } catch (PDOException $e) {
                echo "Error adding constraint: " . $e->getMessage() . "\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Error fixing reservation-user relationship: " . $e->getMessage() . "\n";
}

echo "\n--- Step 3: Re-enabling foreign key checks ---\n";
$pdo->exec("SET foreign_key_checks = 1");
echo "Foreign key checks re-enabled.\n";

echo "\n--- Finished! ---\n";
echo "You should now be able to run doctrine:schema:update --force without errors.\n";

// Function to check if table exists
function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
