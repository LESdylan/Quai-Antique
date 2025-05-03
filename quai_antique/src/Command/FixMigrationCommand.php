<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-migration',
    description: 'Fixes the migration issue by marking the hours_exception migration as executed',
)]
class FixMigrationCommand extends Command
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
        $io->title('Migration Fix Tool');
        
        try {
            // Check if doctrine_migration_versions table exists
            $schemaManager = $this->connection->createSchemaManager();
            if (!$schemaManager->tablesExist(['doctrine_migration_versions'])) {
                $io->error('Migration table not found. Run migrations:migrate first to initialize the database.');
                return Command::FAILURE;
            }
            
            // Check if the migration is already in the table
            $migrationVersion = 'DoctrineMigrations\\Version20231015000000';
            $stmt = $this->connection->prepare('SELECT * FROM doctrine_migration_versions WHERE version = ?');
            $result = $stmt->executeQuery([$migrationVersion]);
            
            if ($result->rowCount() > 0) {
                $io->info('Migration already marked as executed. No action needed.');
                return Command::SUCCESS;
            }
            
            // Mark the migration as executed
            $now = new \DateTime();
            $this->connection->executeStatement(
                'INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES (?, ?, ?)',
                [
                    $migrationVersion,
                    $now->format('Y-m-d H:i:s'),
                    0
                ]
            );
            
            $io->success('Migration marked as executed!');
            $io->info('You can now run doctrine:migrations:migrate again to apply remaining migrations.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
