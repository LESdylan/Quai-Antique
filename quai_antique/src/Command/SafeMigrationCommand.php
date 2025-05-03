<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:safe-migration',
    description: 'Safely adds the is_featured column to the dish table',
)]
class SafeMigrationCommand extends Command
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
        $io->title('Safe Migration Tool');

        try {
            // Check if the is_featured column exists in the dish table
            $schemaManager = $this->connection->createSchemaManager();
            $columns = $schemaManager->listTableColumns('dish');
            $hasIsFeatured = false;
            
            foreach ($columns as $column) {
                if ($column->getName() === 'is_featured') {
                    $hasIsFeatured = true;
                    break;
                }
            }
            
            if (!$hasIsFeatured) {
                $io->note('Adding is_featured column to dish table...');
                
                $this->connection->executeStatement('ALTER TABLE dish ADD is_featured TINYINT(1) DEFAULT 0');
                $io->success('is_featured column added successfully!');
            } else {
                $io->success('is_featured column already exists in dish table.');
            }
            
            // Add other migrations as needed
            
            // Register the is_featured column in migration_versions
            $this->registerMigration($io);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function registerMigration(SymfonyStyle $io): void
    {
        try {
            $versionName = 'DoctrineMigrations\\Version' . date('YmdHis');
            
            if ($this->connection->createSchemaManager()->tablesExist(['doctrine_migration_versions'])) {
                $existingVersion = $this->connection->executeQuery(
                    'SELECT version FROM doctrine_migration_versions WHERE version = ?', 
                    [$versionName]
                )->fetchOne();
                
                if (!$existingVersion) {
                    $this->connection->executeStatement(
                        'INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (?, ?, ?)',
                        [$versionName, date('Y-m-d H:i:s'), 0]
                    );
                    $io->success("Migration $versionName registered in doctrine_migration_versions table");
                }
            } else {
                $io->note('doctrine_migration_versions table does not exist - skipping migration registration');
            }
        } catch (\Exception $e) {
            $io->note('Could not register migration: ' . $e->getMessage());
        }
    }
}
