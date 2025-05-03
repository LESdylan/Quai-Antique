<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:schema:validate',
    description: 'Validates the database schema without problematic queries',
)]
class CustomSchemaValidateCommand extends Command
{
    private $entityManager;
    private $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix database schema issues')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force schema update without asking for confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Schema Validation');

        // 1. Validate mapping files
        $io->section('Mapping Validation');
        $validator = new SchemaValidator($this->entityManager);
        $mappingErrors = $validator->validateMapping();
        
        if (count($mappingErrors) > 0) {
            $io->error('Mapping errors found:');
            foreach ($mappingErrors as $className => $errors) {
                $io->writeln("<comment>Class:</comment> {$className}");
                foreach ($errors as $error) {
                    $io->writeln(" - {$error}");
                }
            }
            $mappingValid = false;
        } else {
            $io->success('The mapping files are correct.');
            $mappingValid = true;
        }

        // 2. Perform custom schema validation
        $io->section('Database Schema Validation');
        
        try {
            // Get tables that should exist based on entity classes
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $expectedTables = [];
            foreach ($metadata as $classMeta) {
                if (!$classMeta->isMappedSuperclass && !$classMeta->isEmbeddedClass) {
                    $expectedTables[] = $classMeta->getTableName();
                }
            }
            
            // Get actual tables from the database
            $dbName = $this->connection->getDatabase();
            $schemaManager = $this->connection->createSchemaManager();
            
            try {
                $actualTables = $schemaManager->listTableNames();
            } catch (\Exception $e) {
                // Fallback to raw SQL query if schema manager fails
                $stmt = $this->connection->executeQuery("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
                $actualTables = $stmt->fetchFirstColumn();
            }
            
            $io->writeln("<info>Expected tables:</info> " . implode(', ', $expectedTables));
            $io->writeln("<info>Actual tables:</info> " . implode(', ', $actualTables));
            
            // Compare expected and actual tables
            $missingTables = array_diff($expectedTables, $actualTables);
            $extraTables = array_diff($actualTables, $expectedTables);
            
            if (!empty($missingTables)) {
                $io->warning('Missing tables in database: ' . implode(', ', $missingTables));
                $schemaValid = false;
            } else {
                $io->success('All expected tables exist in the database.');
                $schemaValid = true;
            }
            
            if (!empty($extraTables)) {
                $io->note('Extra tables in database: ' . implode(', ', $extraTables));
            }
            
            // Check specific columns for a sample of tables
            $sampleSize = min(3, count($expectedTables));
            $checkedTables = array_slice($expectedTables, 0, $sampleSize);
            
            if (!empty($checkedTables)) {
                $io->writeln("<info>Checking columns for sample tables...</info>");
                
                foreach ($checkedTables as $table) {
                    if (!in_array($table, $actualTables)) {
                        continue; // Skip tables that don't exist
                    }
                    
                    try {
                        $columns = $schemaManager->listTableColumns($table);
                        $columnNames = [];
                        foreach ($columns as $column) {
                            $columnNames[] = $column->getName();
                        }
                        $io->writeln("Table <info>{$table}</info> columns: " . implode(', ', $columnNames));
                    } catch (\Exception $e) {
                        $io->warning("Could not check columns for table {$table}: " . $e->getMessage());
                    }
                }
            }
            
        } catch (\Exception $e) {
            $io->error('Error during schema validation: ' . $e->getMessage());
            $schemaValid = false;
        }
        
        // 3. Fix schema if requested
        if ($input->getOption('fix') && !$schemaValid) {
            $io->section('Fixing Schema');
            
            if (!$input->getOption('force')) {
                $confirm = $io->confirm('Do you want to update the database schema?', false);
                if (!$confirm) {
                    return Command::FAILURE;
                }
            }
            
            try {
                $io->writeln('Updating database schema...');
                $updateCmd = $this->getApplication()->find('doctrine:schema:update');
                $updateInput = new \Symfony\Component\Console\Input\ArrayInput([
                    '--force' => true,
                ]);
                $updateCmd->run($updateInput, $output);
                $io->success('Database schema updated successfully.');
            } catch (\Exception $e) {
                $io->error('Error updating schema: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        // Summary
        $io->section('Validation Summary');
        $io->writeln('Mapping: ' . ($mappingValid ? '✅ Valid' : '❌ Invalid'));
        $io->writeln('Schema: ' . ($schemaValid ? '✅ Valid' : '❌ Invalid'));
        
        if (!$schemaValid) {
            $io->note('Run with --fix option to automatically fix schema issues.');
        }
        
        return ($mappingValid && $schemaValid) ? Command::SUCCESS : Command::FAILURE;
    }
}
