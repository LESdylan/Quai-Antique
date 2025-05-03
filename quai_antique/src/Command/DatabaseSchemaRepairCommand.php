<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:database:repair',
    description: 'Repairs common database schema issues',
)]
class DatabaseSchemaRepairCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addOption('non-interactive', 'i', InputOption::VALUE_NONE, 'Run in non-interactive mode (automatically answers "yes" to all questions)')
            ->addOption('skip-additional', 's', InputOption::VALUE_NONE, 'Skip additional column suggestions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Schema Repair');

        // Get database connection
        $connection = $this->entityManager->getConnection();
        $dbName = $connection->getDatabase();
        
        $io->section('Checking for missing columns');
        
        // List of common entities that might be causing the issue
        $commonTables = ['category', 'restaurant', 'dish', 'food', 'menu', 'allergen', 'gallery', 'user'];
        
        // Added 'category_id', 'start_date' and 'end_date' to the list of columns to check
        $columnsToCheck = ['name', 'title', 'description', 'position', 'price', 'image', 'is_active', 
            'created_at', 'updated_at', 'is_seasonal', 'popularity_score', 'category_id', 'start_date', 'end_date', 'email']; 
        
        // Special column handling for certain tables and column types
        $specialColumns = [
            'dish' => [
                'price' => "ALTER TABLE `dish` ADD `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00'",
                'image' => "ALTER TABLE `dish` ADD `image` VARCHAR(255) NULL",
                'is_active' => "ALTER TABLE `dish` ADD `is_active` TINYINT(1) NOT NULL DEFAULT '1'",
                'created_at' => "ALTER TABLE `dish` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'updated_at' => "ALTER TABLE `dish` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'is_seasonal' => "ALTER TABLE `dish` ADD `is_seasonal` TINYINT(1) NULL",
                'popularity_score' => "ALTER TABLE `dish` ADD `popularity_score` INT NULL",
                'category_id' => "ALTER TABLE `dish` ADD `category_id` INT NULL, ADD CONSTRAINT `fk_dish_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL"
            ],
            'menu' => [
                'price' => "ALTER TABLE `menu` ADD `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00'",
                'is_active' => "ALTER TABLE `menu` ADD `is_active` TINYINT(1) NOT NULL DEFAULT '1'",
                'created_at' => "ALTER TABLE `menu` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'updated_at' => "ALTER TABLE `menu` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'start_date' => "ALTER TABLE `menu` ADD `start_date` DATETIME NULL",
                'end_date' => "ALTER TABLE `menu` ADD `end_date` DATETIME NULL"
            ],
            'gallery' => [
                'image' => "ALTER TABLE `gallery` ADD `image` VARCHAR(255) NULL",
                'filename' => "ALTER TABLE `gallery` ADD `filename` VARCHAR(255) NULL",
                'is_active' => "ALTER TABLE `gallery` ADD `is_active` TINYINT(1) NOT NULL DEFAULT '1'",
                'created_at' => "ALTER TABLE `gallery` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
            ],
            'category' => [
                'is_active' => "ALTER TABLE `category` ADD `is_active` TINYINT(1) NOT NULL DEFAULT '1'"
            ],
            'user' => [
                'email' => "ALTER TABLE `user` ADD `email` VARCHAR(180) NOT NULL",
                'roles' => "ALTER TABLE `user` ADD `roles` JSON NOT NULL",
                'password' => "ALTER TABLE `user` ADD `password` VARCHAR(255) NOT NULL",
                'created_at' => "ALTER TABLE `user` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'updated_at' => "ALTER TABLE `user` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
            ]
        ];
        
        $tablesFixed = 0;
        $errors = [];
        
        // Check each table for missing columns using direct SQL
        foreach ($commonTables as $tableName) {
            try {
                // Check if table exists
                $tableExists = $connection->executeQuery("
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ?
                ", [$dbName, $tableName])->fetchOne();
                
                if ($tableExists) {
                    $io->text("Checking table '$tableName'");
                    
                    // Get columns for this table
                    $columns = $connection->executeQuery("SHOW COLUMNS FROM `$tableName`")->fetchAllAssociative();
                    $columnNames = array_column($columns, 'Field');
                    
                    foreach ($columnsToCheck as $columnToCheck) {
                        if (!in_array($columnToCheck, $columnNames)) {
                            $io->text("Table '$tableName' is missing column '$columnToCheck'");
                            
                            // Use special column handling if defined
                            if (isset($specialColumns[$tableName][$columnToCheck])) {
                                $sql = $specialColumns[$tableName][$columnToCheck];
                            } else {
                                // Default to adding a VARCHAR column
                                $sql = "ALTER TABLE `$tableName` ADD `$columnToCheck` VARCHAR(64) NULL";
                            }
                            
                            $io->text("Executing: $sql");
                            
                            try {
                                $connection->executeStatement($sql);
                                $io->success("Added column '$columnToCheck' to table '$tableName'");
                                $tablesFixed++;
                            } catch (\Exception $e) {
                                $errors[] = "Error adding column to $tableName: " . $e->getMessage();
                                $io->error($e->getMessage());
                            }
                        } else {
                            $io->text("Column '$columnToCheck' already exists in table '$tableName'");
                        }
                    }
                } else {
                    $io->info("Table '$tableName' does not exist, skipping");
                }
            } catch (\Exception $e) {
                $errors[] = "Error checking table $tableName: " . $e->getMessage();
                $io->warning("Could not check table '$tableName': " . $e->getMessage());
            }
        }
        
        // Show summary
        if ($tablesFixed > 0) {
            $io->success("Fixed $tablesFixed column issues");
        } elseif (empty($errors)) {
            $io->info("No column issues found that needed fixing");
        } else {
            $io->warning("Encountered errors while attempting to fix schema");
        }
        
        // Show detailed error log if any
        if (!empty($errors)) {
            $io->section('Error Details');
            foreach ($errors as $error) {
                $io->text("- $error");
            }
        }
        
        // Check all tables that might need a name column
        if (!$input->getOption('skip-additional')) {
            $io->section('Finding all tables that might need a name column');
            $tablesQuery = $connection->executeQuery("SHOW TABLES")->fetchAllAssociative();
            
            foreach ($tablesQuery as $row) {
                $tableName = reset($row); // Get the first column value which is the table name
                
                try {
                    $columnsQuery = $connection->executeQuery("SHOW COLUMNS FROM `$tableName`")->fetchAllAssociative();
                    $hasId = false;
                    $hasName = false;
                    $hasTitle = false;
                    $existingColumns = [];
                    
                    foreach ($columnsQuery as $column) {
                        $existingColumns[] = $column['Field'];
                        if ($column['Field'] === 'id') $hasId = true;
                        if ($column['Field'] === 'name') $hasName = true;
                        if ($column['Field'] === 'title') $hasTitle = true;
                    }
                    
                    // Skip adding name if we already have title and don't have name
                    $skipTable = in_array($tableName, [
                        'messenger_messages', 
                        'doctrine_migration_versions', 
                        'booking', 
                        'booking_table',
                        'user', // users typically have email not name
                        'dish_allergen', // junction table
                        'dish_category', // junction table
                        'menu_dish', // junction table
                    ]);
                    
                    if ($hasId && !$hasName && !$skipTable) {
                        $io->text("Table '$tableName' has id but no name column");
                        $io->text("Existing columns: " . implode(", ", $existingColumns));
                        
                        // If it has a title column, we might not need a name column
                        if ($hasTitle) {
                            $io->note("Table '$tableName' already has a 'title' column which might serve a similar purpose");
                        }
                        
                        $nonInteractive = $input->getOption('non-interactive');
                        $answer = $nonInteractive ? 'y' : $io->ask(
                            "Add 'name' column to '$tableName'? [y/n]", 
                            $hasTitle ? 'n' : 'y'
                        );
                        
                        if (strtolower($answer) === 'y') {
                            $sql = "ALTER TABLE `$tableName` ADD `name` VARCHAR(64) NULL";
                            try {
                                $connection->executeStatement($sql);
                                $io->success("Added column 'name' to table '$tableName'");
                            } catch (\Exception $e) {
                                $io->error($e->getMessage());
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $io->warning("Could not check columns for table '$tableName': " . $e->getMessage());
                }
            }
        }
        
        $io->success('Database schema repair complete!');
        
        return Command::SUCCESS;
    }
}
