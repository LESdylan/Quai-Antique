<?php

namespace App\Command;

use App\Service\SchemaToolHelper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:schema:validate',
    description: 'Validates the database schema using custom compatibility checks',
)]
class DatabaseSchemaValidateCommand extends Command
{
    private $entityManager;
    private $schemaToolHelper;

    public function __construct(EntityManagerInterface $entityManager, SchemaToolHelper $schemaToolHelper)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->schemaToolHelper = $schemaToolHelper;
    }

    protected function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Automatically fix schema issues')
            ->addOption('skip-mapping', null, InputOption::VALUE_NONE, 'Skip mapping validation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Schema Validation');

        $mappingValid = true;
        $databaseValid = true;
        
        // Step 1: Run Doctrine mapping validation if not skipped
        if (!$input->getOption('skip-mapping')) {
            $io->section('Mapping Validation');
            
            try {
                $result = shell_exec('php bin/console doctrine:schema:validate --skip-sync');
                if (strpos($result, '[OK] The mapping files are correct.') !== false) {
                    $io->success('The mapping files are correct.');
                } else {
                    $io->error('There are issues with the mapping files.');
                    $mappingValid = false;
                }
            } catch (\Exception $e) {
                $io->error('Error during mapping validation: ' . $e->getMessage());
                $mappingValid = false;
            }
        }
        
        // Step 2: Run custom database validation
        $io->section('Database Validation');
        
        $connection = $this->entityManager->getConnection();
        
        try {
            // Use our custom schema validation that doesn't rely on information_schema.TABLE_CONSTRAINTS
            $isValid = $this->schemaToolHelper->validateSchema($connection, $io);
            
            if ($isValid) {
                $io->success('The database schema is valid.');
            } else {
                $io->error('The database schema has issues.');
                $databaseValid = false;
                
                // Offer to fix the schema if --fix option is provided
                if ($input->getOption('fix')) {
                    $io->section('Fixing Schema Issues');
                    $this->schemaToolHelper->updateSchema($connection, $io);
                    $io->success('Schema issues have been fixed.');
                } else {
                    $io->note('Run with --fix option to automatically fix these issues.');
                    $io->note('Or run: php bin/console app:schema:create to create all required tables.');
                }
            }
            
        } catch (\Exception $e) {
            $io->error('Error during database validation: ' . $e->getMessage());
            $databaseValid = false;
        }
        
        // Summary
        $io->section('Validation Summary');
        if (!$input->getOption('skip-mapping')) {
            $io->text('Mapping: ' . ($mappingValid ? '✅ Valid' : '❌ Invalid'));
        }
        $io->text('Database: ' . ($databaseValid ? '✅ Valid' : '❌ Invalid'));
        
        return ($mappingValid && $databaseValid) ? Command::SUCCESS : Command::FAILURE;
    }
}
