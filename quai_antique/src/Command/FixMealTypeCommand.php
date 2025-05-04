<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-meal-type',
    description: 'Fixes NULL values in meal_type column and ensures defaults are set properly'
)]
class FixMealTypeCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fixing Menu meal_type issues');

        try {
            // 1. Check if the column exists
            $columnExists = false;
            try {
                $this->connection->executeQuery('SELECT meal_type FROM menu LIMIT 1');
                $columnExists = true;
                $io->success('meal_type column exists in the menu table');
            } catch (\Exception $e) {
                $io->error('meal_type column does not exist. Creating it now...');
            }

            // 2. Create the column if it doesn't exist
            if (!$columnExists) {
                $this->connection->executeStatement('ALTER TABLE menu ADD COLUMN meal_type VARCHAR(255) DEFAULT "main" NOT NULL');
                $io->success('Created meal_type column with default "main" and NOT NULL constraint');
            }

            // 3. Update any NULL values to 'main'
            $updatedRows = $this->connection->executeStatement('UPDATE menu SET meal_type = "main" WHERE meal_type IS NULL OR meal_type = ""');
            $io->success("Updated $updatedRows rows with NULL or empty meal_type to 'main'");

            // 4. Verify there are no NULL values left
            $nullCount = $this->connection->executeQuery('SELECT COUNT(*) FROM menu WHERE meal_type IS NULL OR meal_type = ""')->fetchOne();
            if ($nullCount > 0) {
                $io->warning("Still found $nullCount records with NULL or empty meal_type. Running update again...");
                $this->connection->executeStatement('UPDATE menu SET meal_type = "main" WHERE meal_type IS NULL OR meal_type = ""');
                
                // Check again
                $nullCount = $this->connection->executeQuery('SELECT COUNT(*) FROM menu WHERE meal_type IS NULL OR meal_type = ""')->fetchOne();
                if ($nullCount > 0) {
                    $io->error("Failed to update all records. $nullCount records still have NULL or empty meal_type.");
                    return Command::FAILURE;
                }
            }

            // 5. Ensure the column has a NOT NULL constraint
            try {
                $this->connection->executeStatement('ALTER TABLE menu MODIFY COLUMN meal_type VARCHAR(255) NOT NULL DEFAULT "main"');
                $io->success('Successfully enforced NOT NULL constraint with default "main" on meal_type column');
            } catch (\Exception $e) {
                $io->note('Could not modify column constraints (may already be set): ' . $e->getMessage());
            }

            $io->success('All meal_type issues have been fixed!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
