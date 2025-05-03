<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create:user-table',
    description: 'Creates the user table in the database if it doesn\'t exist',
)]
class CreateUserTableCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Checking/Creating User Table');

        $connection = $this->entityManager->getConnection();
        $dbName = $connection->getDatabase();
        
        try {
            // Check if table already exists
            $tableExists = $connection->executeQuery("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ", [$dbName, 'user'])->fetchOne();
            
            if ($tableExists) {
                $io->info('Table "user" already exists. Checking required columns...');
                
                // Check for required columns
                $columns = $connection->executeQuery("SHOW COLUMNS FROM `user`")->fetchAllAssociative();
                $columnNames = array_column($columns, 'Field');
                
                $requiredColumns = [
                    'email' => "ALTER TABLE `user` ADD `email` VARCHAR(180) NOT NULL",
                    'roles' => "ALTER TABLE `user` ADD `roles` JSON NOT NULL",
                    'password' => "ALTER TABLE `user` ADD `password` VARCHAR(255) NOT NULL",
                    'created_at' => "ALTER TABLE `user` ADD `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
                    'updated_at' => "ALTER TABLE `user` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
                ];
                
                foreach ($requiredColumns as $column => $sql) {
                    if (!in_array($column, $columnNames)) {
                        $io->text("Adding missing '$column' column to user table");
                        try {
                            $connection->executeStatement($sql);
                            $io->success("Added '$column' column to user table");
                        } catch (\Exception $e) {
                            $io->error("Failed to add '$column': " . $e->getMessage());
                        }
                    }
                }
                
                // Make email unique if it's not
                try {
                    $indexes = $connection->executeQuery("SHOW INDEXES FROM `user` WHERE Column_name = 'email'")->fetchAllAssociative();
                    $hasUniqueIndex = false;
                    
                    foreach ($indexes as $index) {
                        if ($index['Non_unique'] == 0) { // 0 means unique
                            $hasUniqueIndex = true;
                            break;
                        }
                    }
                    
                    if (!$hasUniqueIndex) {
                        $io->text("Making email column unique");
                        $connection->executeStatement("ALTER TABLE `user` ADD UNIQUE INDEX `UNIQ_user_email` (`email`)");
                        $io->success("Made email column unique");
                    }
                } catch (\Exception $e) {
                    $io->error("Failed to check/add unique index: " . $e->getMessage());
                }
                
                return Command::SUCCESS;
            }
            
            // Create the user table if it doesn't exist
            $io->text("Creating user table...");
            $sql = "CREATE TABLE `user` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(64) DEFAULT NULL,
                last_name VARCHAR(64) DEFAULT NULL,
                default_guest_count INT DEFAULT NULL,
                allergies TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_user_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $connection->executeStatement($sql);
            $io->success('Successfully created the "user" table');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating/checking user table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
