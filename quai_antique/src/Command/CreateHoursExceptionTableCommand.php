<?php

namespace App\Command;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create:hours-exception-table',
    description: 'Creates the hours_exception table manually',
)]
class CreateHoursExceptionTableCommand extends Command
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

        try {
            $connection = $this->entityManager->getConnection();
            
            // Check if table already exists
            $schemaManager = $connection->createSchemaManager();
            if ($schemaManager->tablesExist(['hours_exception'])) {
                $io->success('The hours_exception table already exists.');
                return Command::SUCCESS;
            }
            
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
            
            $connection->executeStatement($sql);
            
            $io->success('The hours_exception table has been created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create hours_exception table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
