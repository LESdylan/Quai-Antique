<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:fix:reservation-schema',
    description: 'Fixes the reservation table schema to match the entity',
)]
class FixReservationSchemaCommand extends Command
{
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fixing Reservation Table Schema');

        // Check if reservation table exists
        $tableExists = $this->connection->executeQuery("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() AND table_name = 'reservation'
        ")->fetchOne();

        if (!$tableExists) {
            $io->error('Reservation table does not exist!');
            $io->note('Run "php bin/console doctrine:schema:create" to create all tables first');
            return Command::FAILURE;
        }

        // Check for missing columns
        $missingColumns = $this->checkMissingColumns($io);
        
        if (count($missingColumns) === 0) {
            $io->success('All columns already exist in the reservation table');
            return Command::SUCCESS;
        }

        // Add missing columns
        foreach ($missingColumns as $column => $definition) {
            $io->text("Adding '$column' column to reservation table...");
            
            try {
                $this->connection->executeStatement("ALTER TABLE `reservation` ADD $definition");
                $io->success("Added '$column' column successfully");
            } catch (\Exception $e) {
                $io->error("Failed to add '$column' column: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Add foreign key constraint for user_id if needed
        if (isset($missingColumns['user_id'])) {
            $io->text("Adding foreign key constraint for user_id...");
            
            try {
                // First check if user table exists
                $userTableExists = $this->connection->executeQuery("
                    SELECT COUNT(*) 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() AND table_name = 'user'
                ")->fetchOne();

                if ($userTableExists) {
                    $this->connection->executeStatement("
                        ALTER TABLE `reservation` 
                        ADD CONSTRAINT `FK_reservation_user` 
                        FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
                    ");
                    $io->success("Added foreign key constraint for user_id successfully");
                } else {
                    $io->warning("User table doesn't exist, skipping foreign key constraint");
                }
            } catch (\Exception $e) {
                $io->warning("Failed to add foreign key constraint: " . $e->getMessage());
                $io->note("The column was added but without the constraint");
            }
        }

        $io->success('Reservation table schema has been fixed!');
        return Command::SUCCESS;
    }

    private function checkMissingColumns(SymfonyStyle $io): array
    {
        $io->section('Checking for missing columns');
        
        // Get existing columns
        $existingColumns = $this->connection->executeQuery("SHOW COLUMNS FROM `reservation`")
                                            ->fetchFirstColumn();
        
        // Define required columns with their definitions
        $requiredColumns = [
            'last_name' => "COLUMN `last_name` VARCHAR(64) NOT NULL",
            'first_name' => "COLUMN `first_name` VARCHAR(64) NULL",
            'user_id' => "COLUMN `user_id` INT NULL",
            'email' => "COLUMN `email` VARCHAR(180) NOT NULL",
            'phone' => "COLUMN `phone` VARCHAR(20) NOT NULL",
            'status' => "COLUMN `status` VARCHAR(20) NOT NULL",
            'notes' => "COLUMN `notes` TEXT NULL",
            'allergies' => "COLUMN `allergies` TEXT NULL",
            'created_at' => "COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ];
        
        // Find missing columns
        $missingColumns = [];
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                $io->text("Column '$column' is missing");
                $missingColumns[$column] = $definition;
            } else {
                $io->text("Column '$column' already exists ✓");
            }
        }
        
        return $missingColumns;
    }
}
