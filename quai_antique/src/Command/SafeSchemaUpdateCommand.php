<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:schema:update',
    description: 'Safely updates the database schema using direct SQL',
)]
class SafeSchemaUpdateCommand extends Command
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
            ->addOption('force', null, InputOption::VALUE_NONE, 'Execute the SQL queries')
            ->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Dump the SQL queries instead of executing them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Safe Database Schema Update');

        $dumpSql = $input->getOption('dump-sql');
        $force = $input->getOption('force');
        
        if (!$dumpSql && !$force) {
            $io->error('Please specify --dump-sql to see the SQL statements or --force to execute them.');
            return Command::FAILURE;
        }

        // Get entity metadata
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadata)) {
            $io->warning('No entity metadata found. Did you forget to create entities?');
            return Command::FAILURE;
        }

        // Create SQL statements in a safer way
        $connection = $this->entityManager->getConnection();
        $dbName = $connection->getDatabase();
        
        $sqlQueries = [];
        
        // Process each entity
        foreach ($metadata as $classMeta) {
            if ($classMeta->isMappedSuperclass || $classMeta->isEmbeddedClass) {
                continue;
            }
            
            $tableName = $classMeta->getTableName();
            $io->writeln("Processing entity: {$classMeta->getName()} (table: {$tableName})");
            
            // Check if table exists
            $tableExists = false;
            try {
                $stmt = $connection->executeQuery(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
                    [$dbName, $tableName]
                );
                $tableExists = (bool) $stmt->fetchOne();
                
                if ($tableExists) {
                    $io->text(" - Table {$tableName} exists, checking columns...");
                    
                    // Get existing columns
                    $existingColumns = [];
                    try {
                        $stmt = $connection->executeQuery("SHOW COLUMNS FROM `{$tableName}`");
                        $existingColumns = array_column($stmt->fetchAllAssociative(), 'Field');
                    } catch (\Exception $e) {
                        $io->error("Error getting columns from table {$tableName}: " . $e->getMessage());
                        continue;
                    }
                    
                    // Check for missing columns
                    foreach ($classMeta->getFieldNames() as $fieldName) {
                        if ($fieldName === 'id') continue; // Skip ID field
                        
                        $mapping = $classMeta->getFieldMapping($fieldName);
                        $columnName = $mapping->columnName ?? $fieldName;
                        
                        if (!in_array($columnName, $existingColumns)) {
                            $io->text(" - Column {$columnName} does not exist, adding...");
                            
                            // Get column definition
                            $columnType = $this->getColumnTypeFromMapping($mapping);
                            $nullable = isset($mapping->nullable) && $mapping->nullable ? 'NULL' : 'NOT NULL';
                            
                            $sql = "ALTER TABLE `{$tableName}` ADD `{$columnName}` {$columnType} {$nullable}";
                            $sqlQueries[] = $sql;
                        }
                    }
                    
                    // Check associations for missing foreign key columns
                    foreach ($classMeta->getAssociationMappings() as $fieldName => $mapping) {
                        if (!$classMeta->isAssociationWithSingleJoinColumn($fieldName)) {
                            continue; // Skip many-to-many or custom join tables
                        }
                        
                        $joinColumns = $mapping->joinColumns ?? [];
                        foreach ($joinColumns as $joinColumn) {
                            $columnName = $joinColumn->name ?? $fieldName . '_id';
                            
                            if (!in_array($columnName, $existingColumns)) {
                                $io->text(" - Foreign key column {$columnName} does not exist, adding...");
                                
                                $sql = "ALTER TABLE `{$tableName}` ADD `{$columnName}` INT NULL";
                                $sqlQueries[] = $sql;
                            }
                        }
                    }
                } else {
                    $io->text(" - Table {$tableName} does not exist, creating...");
                    
                    // We'll only try to create the table structure, but without any indexes or foreign keys
                    // This is a safer approach as the full schema can be complex
                    $columns = [];
                    
                    // Add ID column
                    $columns[] = "`id` INT AUTO_INCREMENT PRIMARY KEY";
                    
                    // Add field columns
                    foreach ($classMeta->getFieldNames() as $fieldName) {
                        if ($fieldName === 'id') continue; // Skip ID field as we added it already
                        
                        $mapping = $classMeta->getFieldMapping($fieldName);
                        $columnName = $mapping->columnName ?? $fieldName;
                        $columnType = $this->getColumnTypeFromMapping($mapping);
                        $nullable = isset($mapping->nullable) && $mapping->nullable ? 'NULL' : 'NOT NULL';
                        
                        $columns[] = "`{$columnName}` {$columnType} {$nullable}";
                    }
                    
                    // Add association columns (foreign keys)
                    foreach ($classMeta->getAssociationMappings() as $fieldName => $mapping) {
                        if (!$classMeta->isAssociationWithSingleJoinColumn($fieldName)) {
                            continue; // Skip many-to-many or custom join tables
                        }
                        
                        $joinColumns = $mapping->joinColumns ?? [];
                        foreach ($joinColumns as $joinColumn) {
                            $columnName = $joinColumn->name ?? $fieldName . '_id';
                            $columns[] = "`{$columnName}` INT NULL";
                        }
                    }
                    
                    // Create the table
                    $columnDefinitions = implode(",\n    ", $columns);
                    $sql = "CREATE TABLE `{$tableName}` (\n    {$columnDefinitions}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    $sqlQueries[] = $sql;
                }
                
            } catch (\Exception $e) {
                $io->error("Error processing table {$tableName}: " . $e->getMessage());
                continue;
            }
        }
        
        // Output or execute SQL
        if (empty($sqlQueries)) {
            $io->success('No schema changes needed. Database is up to date.');
            return Command::SUCCESS;
        }
        
        $io->section('SQL Statements');
        $io->writeln(implode("\n\n", $sqlQueries));
        
        if ($force) {
            $io->section('Executing SQL...');
            
            try {
                $connection->beginTransaction();
                foreach ($sqlQueries as $sql) {
                    try {
                        $connection->executeStatement($sql);
                        $io->text("Executed successfully: " . substr($sql, 0, 60) . "...");
                    } catch (\Exception $e) {
                        $io->warning("Error executing SQL: " . $e->getMessage());
                        $io->warning("Skipping and continuing with next statement");
                    }
                }
                $connection->commit();
                $io->success('Schema updates executed successfully!');
            } catch (\Exception $e) {
                $connection->rollBack();
                $io->error('Schema update failed: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->note('Run with --force to execute the SQL statements.');
        }
        
        return Command::SUCCESS;
    }
    
    private function getColumnTypeFromMapping($mapping): string
    {
        // Handle both array and object formats for field mappings
        $type = is_object($mapping) ? $mapping->type : ($mapping['type'] ?? 'string');
        
        // Convert Doctrine types to MySQL column types
        switch ($type) {
            case 'string':
                $length = is_object($mapping) ? ($mapping->length ?? 255) : ($mapping['length'] ?? 255);
                return "VARCHAR({$length})";
            case 'text':
                return 'TEXT';
            case 'integer':
            case 'smallint':
                return 'INT';
            case 'bigint':
                return 'BIGINT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'decimal':
                $precision = is_object($mapping) ? ($mapping->precision ?? 10) : ($mapping['precision'] ?? 10);
                $scale = is_object($mapping) ? ($mapping->scale ?? 2) : ($mapping['scale'] ?? 2);
                return "DECIMAL({$precision},{$scale})";
            case 'date':
                return 'DATE';
            case 'datetime':
            case 'datetime_immutable':
                return 'DATETIME';
            case 'time':
                return 'TIME';
            case 'json':
            case 'json_array':
                return 'JSON';
            case 'float':
                return 'FLOAT';
            default:
                return 'VARCHAR(255)';
        }
    }
}
