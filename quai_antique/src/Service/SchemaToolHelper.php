<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class SchemaToolHelper
{
    // Tables required for the application
    private $requiredTables = [
        'user' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'email' => 'VARCHAR(180) NOT NULL',
            'roles' => 'JSON NOT NULL',
            'password' => 'VARCHAR(255) NOT NULL',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NOT NULL'
        ],
        'reservation' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'date' => 'DATETIME NOT NULL',
            'guest_count' => 'INT NOT NULL',
            'last_name' => 'VARCHAR(64) NOT NULL',
            'first_name' => 'VARCHAR(64) NULL',
            'email' => 'VARCHAR(180) NOT NULL',
            'phone' => 'VARCHAR(20) NOT NULL',
            'status' => 'VARCHAR(20) NOT NULL',
            'notes' => 'TEXT NULL',
            'allergies' => 'TEXT NULL',
            'created_at' => 'DATETIME NOT NULL',
            'user_id' => 'INT NULL'
        ],
        'category' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(64) NOT NULL',
            'description' => 'TEXT NULL',
            'position' => 'INT DEFAULT 0'
        ],
        'dish' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(64) NOT NULL',
            'description' => 'TEXT NULL',
            'price' => 'DECIMAL(10,2) NOT NULL',
            'image' => 'VARCHAR(255) NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NOT NULL',
            'category_id' => 'INT NULL',
            'is_seasonal' => 'TINYINT(1) NULL',
            'popularity_score' => 'INT NULL'
        ],
        'hours' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'day_of_week' => 'INT NOT NULL',
            'lunch_opening_time' => 'TIME NULL',
            'lunch_closing_time' => 'TIME NULL',
            'dinner_opening_time' => 'TIME NULL',
            'dinner_closing_time' => 'TIME NULL',
            'is_closed' => 'TINYINT(1) NOT NULL DEFAULT 0'
        ],
        'image' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'filename' => 'VARCHAR(255) NOT NULL',
            'original_filename' => 'VARCHAR(255) NULL',
            'mime_type' => 'VARCHAR(255) NULL',
            'alt' => 'VARCHAR(255) NOT NULL',
            'category' => 'VARCHAR(64) NULL',
            'dish_id' => 'INT NULL',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1'
        ]
    ];

    /**
     * Validates if all required tables exist and have the necessary columns
     */
    public function validateSchema(Connection $connection, SymfonyStyle $io = null): bool
    {
        $valid = true;
        $dbName = $connection->getDatabase();
        
        foreach ($this->requiredTables as $tableName => $columns) {
            try {
                // Check if table exists
                $tableExists = $connection->executeQuery("
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ?
                ", [$dbName, $tableName])->fetchOne();
                
                if (!$tableExists) {
                    if ($io) {
                        $io->error("Table '$tableName' does not exist!");
                    }
                    $valid = false;
                    continue;
                }
                
                // Check if columns exist
                $existingColumns = $connection->executeQuery("SHOW COLUMNS FROM `$tableName`")->fetchAllAssociative();
                $existingColumnNames = array_column($existingColumns, 'Field');
                
                foreach (array_keys($columns) as $columnName) {
                    if ($columnName === 'id') continue; // Skip ID check as it's always required
                    
                    if (!in_array($columnName, $existingColumnNames)) {
                        if ($io) {
                            $io->warning("Column '$columnName' missing in table '$tableName'");
                        }
                        $valid = false;
                    }
                }
                
            } catch (\Exception $e) {
                if ($io) {
                    $io->error("Error checking table '$tableName': " . $e->getMessage());
                }
                $valid = false;
            }
        }
        
        return $valid;
    }

    /**
     * Creates all required tables directly using SQL
     */
    public function createTables(Connection $connection, SymfonyStyle $io = null): void
    {
        $dbName = $connection->getDatabase();
        
        foreach ($this->requiredTables as $tableName => $columns) {
            try {
                // Check if table exists
                $tableExists = $connection->executeQuery("
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ?
                ", [$dbName, $tableName])->fetchOne();
                
                if (!$tableExists) {
                    if ($io) {
                        $io->text("Creating table '$tableName'");
                    }
                    
                    // Build CREATE TABLE statement
                    $columnDefinitions = [];
                    foreach ($columns as $columnName => $definition) {
                        $columnDefinitions[] = "`$columnName` $definition";
                    }
                    
                    // Add foreign key constraints for known relationships
                    $foreignKeys = [];
                    if ($tableName == 'reservation' && in_array('user_id', array_keys($columns))) {
                        $foreignKeys[] = "CONSTRAINT `fk_reservation_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL";
                    }
                    if ($tableName == 'dish' && in_array('category_id', array_keys($columns))) {
                        $foreignKeys[] = "CONSTRAINT `fk_dish_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL";
                    }
                    if ($tableName == 'image' && in_array('dish_id', array_keys($columns))) {
                        $foreignKeys[] = "CONSTRAINT `fk_image_dish` FOREIGN KEY (`dish_id`) REFERENCES `dish` (`id`) ON DELETE SET NULL";
                    }
                    
                    // Combine column definitions and foreign keys
                    $tableDefinition = implode(",\n    ", array_merge($columnDefinitions, $foreignKeys));
                    
                    // Create the table
                    $sql = "CREATE TABLE `$tableName` (\n    $tableDefinition\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    $connection->executeStatement($sql);
                    
                    if ($io) {
                        $io->success("Created table '$tableName'");
                    }
                } else if ($io) {
                    $io->info("Table '$tableName' already exists");
                    
                    // Check for missing columns
                    $this->addMissingColumns($connection, $tableName, $columns, $io);
                }
                
            } catch (\Exception $e) {
                if ($io) {
                    $io->error("Error creating table '$tableName': " . $e->getMessage());
                }
                throw $e;
            }
        }
    }
    
    /**
     * Adds any missing columns to an existing table
     */
    private function addMissingColumns(Connection $connection, string $tableName, array $columns, SymfonyStyle $io = null): void
    {
        try {
            // Get existing columns
            $existingColumns = $connection->executeQuery("SHOW COLUMNS FROM `$tableName`")->fetchAllAssociative();
            $existingColumnNames = array_column($existingColumns, 'Field');
            
            foreach ($columns as $columnName => $definition) {
                if ($columnName === 'id') continue; // Skip ID column
                
                if (!in_array($columnName, $existingColumnNames)) {
                    if ($io) {
                        $io->text("Adding missing column '$columnName' to table '$tableName'");
                    }
                    
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $definition";
                    $connection->executeStatement($sql);
                    
                    if ($io) {
                        $io->success("Added column '$columnName' to table '$tableName'");
                    }
                }
            }
            
        } catch (\Exception $e) {
            if ($io) {
                $io->error("Error adding columns to '$tableName': " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Safe update of schema that doesn't rely on Doctrine's schema tools
     */
    public function updateSchema(Connection $connection, SymfonyStyle $io = null): void
    {
        // First create any missing tables
        $this->createTables($connection, $io);
        
        // Then ensure all foreign key constraints exist
        $this->ensureForeignKeys($connection, $io);
    }
    
    /**
     * Ensures all foreign key constraints exist
     */
    private function ensureForeignKeys(Connection $connection, SymfonyStyle $io = null): void
    {
        $relationships = [
            [
                'table' => 'reservation',
                'column' => 'user_id',
                'referenced_table' => 'user',
                'referenced_column' => 'id',
                'constraint_name' => 'fk_reservation_user'
            ],
            [
                'table' => 'dish',
                'column' => 'category_id',
                'referenced_table' => 'category',
                'referenced_column' => 'id',
                'constraint_name' => 'fk_dish_category'
            ],
            [
                'table' => 'image',
                'column' => 'dish_id',
                'referenced_table' => 'dish',
                'referenced_column' => 'id',
                'constraint_name' => 'fk_image_dish'
            ]
        ];
        
        foreach ($relationships as $rel) {
            try {
                // Check if constraint exists
                $constraints = $connection->executeQuery("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND CONSTRAINT_NAME = ?
                ", [$rel['table'], $rel['constraint_name']])->fetchAllAssociative();
                
                if (empty($constraints)) {
                    if ($io) {
                        $io->text("Adding foreign key constraint '{$rel['constraint_name']}' to table '{$rel['table']}'");
                    }
                    
                    try {
                        $sql = "ALTER TABLE `{$rel['table']}` 
                                ADD CONSTRAINT `{$rel['constraint_name']}` 
                                FOREIGN KEY (`{$rel['column']}`) 
                                REFERENCES `{$rel['referenced_table']}` (`{$rel['referenced_column']}`) 
                                ON DELETE SET NULL";
                        $connection->executeStatement($sql);
                        
                        if ($io) {
                            $io->success("Added foreign key constraint '{$rel['constraint_name']}'");
                        }
                    } catch (\Exception $e) {
                        // If this fails, it might be because the column doesn't exist or similar issue
                        if ($io) {
                            $io->warning("Could not add foreign key: " . $e->getMessage());
                        }
                    }
                }
                
            } catch (\Exception $e) {
                if ($io) {
                    $io->warning("Error checking constraint '{$rel['constraint_name']}': " . $e->getMessage());
                }
            }
        }
    }
}
