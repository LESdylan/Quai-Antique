<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:fix-database',
    description: 'Fix database structure issues',
)]
class FixDatabaseCommand extends Command
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

        $io->title('Database Fix Tool');
        
        try {
            // Check if hours_exception table exists
            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            if (!in_array('hours_exception', $tables)) {
                $io->note('Creating hours_exception table...');
                
                // SQL to create the hours_exception table
                $sql = "
                    CREATE TABLE hours_exception (
                        id INT AUTO_INCREMENT NOT NULL,
                        date DATE NOT NULL,
                        description VARCHAR(255) NOT NULL,
                        is_closed TINYINT(1) NOT NULL,
                        opening_time VARCHAR(5) DEFAULT NULL,
                        closing_time VARCHAR(5) DEFAULT NULL,
                        PRIMARY KEY(id)
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                ";
                
                $this->connection->executeStatement($sql);
                $io->success('hours_exception table created successfully!');
            } else {
                $io->success('hours_exception table already exists.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
