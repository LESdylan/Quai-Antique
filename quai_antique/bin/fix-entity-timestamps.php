#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\ConsoleOutput;

// Set up output
$output = new ConsoleOutput();
$output->writeln('<info>Entity Timestamp Fixer</info>');
$output->writeln('===========================');

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root'; 
$dbPass = 'MO3848seven_36';
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

$output->writeln("Connecting to database: $dbName on $dbHost...");

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $output->writeln('<info>Connected successfully!</info>');
} catch (PDOException $e) {
    $output->writeln('<error>Connection failed: ' . $e->getMessage() . '</error>');
    exit(1);
}

// Step 1: Find all entities in the project
$output->writeln("\n<info>Step 1: Finding all entities in project...</info>");
$entityFiles = [];
$entityDir = dirname(__DIR__) . '/src/Entity';

if (is_dir($entityDir)) {
    $finder = new Finder();
    $finder->files()->in($entityDir)->name('*.php')->notName('*.gitignore');
    
    foreach ($finder as $file) {
        $entityFiles[] = $file->getRealPath();
        $output->writeln('  Found: ' . $file->getFilename());
    }
}

if (empty($entityFiles)) {
    $output->writeln('<error>No entity files found!</error>');
    exit(1);
}

// Step 2: Parse entities and extract table names
$output->writeln("\n<info>Step 2: Mapping entities to tables...</info>");
$entityToTable = [];
$tableTimestampFields = [];

foreach ($entityFiles as $file) {
    $content = file_get_contents($file);
    preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
    preg_match('/class\s+(\w+)/', $content, $classMatch);
    
    if (!isset($namespaceMatch[1]) || !isset($classMatch[1])) {
        $output->writeln('  <comment>Skipping invalid entity: ' . basename($file) . '</comment>');
        continue;
    }
    
    $className = $classMatch[1];
    $namespace = $namespaceMatch[1];
    
    // Check for table annotation
    preg_match('/#\[ORM\\\\Entity.*?]/', $content, $entityMatch);
    
    if (!isset($entityMatch[0])) {
        $output->writeln('  <comment>Skipping non-entity class: ' . $className . '</comment>');
        continue;
    }
    
    // Check if it's using the UpdateTimestampsTrait
    $hasTrait = strpos($content, 'use UpdateTimestampsTrait') !== false;
    
    // Check for lifecycle callbacks
    $hasLifecycleCallbacks = strpos($content, '#[ORM\HasLifecycleCallbacks]') !== false;
    
    // Map standard naming convention (singular Entity name to plural table name)
    $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
    
    // Check for custom table name
    preg_match('/#\[ORM\\\\Table\(name: [\'"]([^\'"]+)[\'"]/', $content, $tableMatch);
    if (isset($tableMatch[1])) {
        $tableName = $tableMatch[1];
    }
    
    // Check for timestamp fields
    $timestampFields = [];
    if (preg_match_all('/private\s+\?\\\\DateTimeInterface\s+\$(\w+)/', $content, $fieldMatches)) {
        foreach ($fieldMatches[1] as $field) {
            if (stripos($field, 'creat') !== false || stripos($field, 'updat') !== false) {
                $timestampFields[] = $field;
            }
        }
    }
    
    $entityToTable[$className] = [
        'file' => $file,
        'table' => $tableName, 
        'hasTrait' => $hasTrait,
        'hasLifecycleCallbacks' => $hasLifecycleCallbacks,
        'timestampFields' => $timestampFields,
        'namespace' => $namespace
    ];
    
    $output->writeln("  Mapped entity '$className' to table '$tableName'");
    if (!empty($timestampFields)) {
        $output->writeln("    Timestamp fields: " . implode(', ', $timestampFields));
    }
}

// Step 3: Check for tables with timestamp fields
$output->writeln("\n<info>Step 3: Checking database tables for timestamp fields...</info>");

foreach ($entityToTable as $entity => $info) {
    $table = $info['table'];
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tableTimestampFields = [];
        foreach ($columns as $column) {
            $field = $column['Field'];
            $type = $column['Type'];
            $isNull = $column['Null'] === 'YES';
            $default = $column['Default'];
            
            if (stripos($type, 'date') !== false || stripos($type, 'time') !== false) {
                if (stripos($field, 'creat') !== false || stripos($field, 'updat') !== false) {
                    $tableTimestampFields[$field] = [
                        'type' => $type, 
                        'isNull' => $isNull, 
                        'default' => $default
                    ];
                }
            }
        }
        
        if (!empty($tableTimestampFields)) {
            $output->writeln("  Table '$table' has timestamp fields:");
            foreach ($tableTimestampFields as $field => $metadata) {
                $output->writeln("    - $field ({$metadata['type']}, Nullable: " . 
                                ($metadata['isNull'] ? 'YES' : 'NO') . 
                                ", Default: " . ($metadata['default'] ?? 'NULL') . ")");
            }
        }
        
        // Step 4: Fix any issues with timestamp fields
        if (!empty($tableTimestampFields)) {
            $output->writeln("  Checking for timestamp fields that need fixes...");
            
            foreach ($tableTimestampFields as $field => $metadata) {
                if (!$metadata['isNull'] && $metadata['default'] === null) {
                    $output->writeln("    <comment>Field '$field' is NOT NULL but has no default value. Fixing...</comment>");
                    
                    try {
                        if (stripos($metadata['type'], 'datetime') !== false) {
                            $pdo->exec("ALTER TABLE `$table` MODIFY `$field` {$metadata['type']} NOT NULL DEFAULT CURRENT_TIMESTAMP");
                            $output->writeln("    <info>Added DEFAULT CURRENT_TIMESTAMP to $field</info>");
                        } elseif (stripos($metadata['type'], 'date') !== false) {
                            $pdo->exec("ALTER TABLE `$table` MODIFY `$field` {$metadata['type']} NOT NULL DEFAULT (CURRENT_DATE)");
                            $output->writeln("    <info>Added DEFAULT CURRENT_DATE to $field</info>");
                        }
                    } catch (PDOException $e) {
                        $output->writeln("    <error>Failed to fix field '$field': " . $e->getMessage() . "</error>");
                        
                        // Alternative approach - make it nullable
                        try {
                            $output->writeln("    <comment>Trying to make field '$field' nullable instead...</comment>");
                            $pdo->exec("ALTER TABLE `$table` MODIFY `$field` {$metadata['type']} NULL");
                            $output->writeln("    <info>Made field '$field' nullable</info>");
                        } catch (PDOException $e2) {
                            $output->writeln("    <error>Failed to make field nullable: " . $e2->getMessage() . "</error>");
                        }
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $output->writeln("  <comment>Could not check table '$table': " . $e->getMessage() . "</comment>");
    }
}

// Step 5: Update entity classes to use the trait if needed
$output->writeln("\n<info>Step 5: Checking entity classes for timestamp trait usage...</info>");

$traitNeededCount = 0;

foreach ($entityToTable as $entity => $info) {
    $file = $info['file'];
    $timestampFields = $info['timestampFields'];
    $hasTrait = $info['hasTrait'];
    $hasLifecycleCallbacks = $info['hasLifecycleCallbacks'];
    
    if (!empty($timestampFields) && (!$hasTrait || !$hasLifecycleCallbacks)) {
        $traitNeededCount++;
        $output->writeln("  Entity '$entity' has timestamp fields but " . 
                         (!$hasTrait ? "doesn't use UpdateTimestampsTrait" : "") . 
                         (!$hasTrait && !$hasLifecycleCallbacks ? " and " : "") .
                         (!$hasLifecycleCallbacks ? "doesn't have lifecycle callbacks" : ""));
        
        $content = file_get_contents($file);
        $modified = false;
        
        // Add trait if needed
        if (!$hasTrait) {
            $output->writeln("    Adding UpdateTimestampsTrait usage...");
            
            // Add use statement
            if (strpos($content, 'use App\\Entity\\UpdateTimestampsTrait;') === false) {
                $insertPos = strpos($content, 'use ') + strlen('use ');
                $endOfImports = strpos($content, 'class ' . $entity);
                $content = substr($content, 0, $insertPos) . 
                           "App\\Entity\\UpdateTimestampsTrait;\n" . 
                           substr($content, $insertPos);
                $modified = true;
            }
            
            // Add trait usage in class
            $classStart = strpos($content, '{', strpos($content, 'class ' . $entity));
            if ($classStart !== false) {
                $content = substr($content, 0, $classStart + 1) . 
                           "\n    use UpdateTimestampsTrait;\n" . 
                           substr($content, $classStart + 1);
                $modified = true;
            }
        }
        
        // Add lifecycle callbacks annotation if needed
        if (!$hasLifecycleCallbacks) {
            $output->writeln("    Adding @ORM\HasLifecycleCallbacks...");
            
            $entityAnnotationEnd = strpos($content, ']', strpos($content, '#[ORM\\Entity'));
            if ($entityAnnotationEnd !== false) {
                $content = substr($content, 0, $entityAnnotationEnd + 1) . 
                           "\n#[ORM\\HasLifecycleCallbacks]" . 
                           substr($content, $entityAnnotationEnd + 1);
                $modified = true;
            }
        }
        
        if ($modified) {
            file_put_contents($file, $content);
            $output->writeln("    <info>Updated entity file</info>");
        }
    }
}

if ($traitNeededCount === 0) {
    $output->writeln("  <info>All entities with timestamp fields are properly configured.</info>");
}

// Step 6: Check if any NULL values need to be updated in the database
$output->writeln("\n<info>Step 6: Checking for NULL timestamp values in database...</info>");

foreach ($entityToTable as $entity => $info) {
    $table = $info['table'];
    $hasNullValues = false;
    
    try {
        // Check timestamp columns
        $result = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Type LIKE '%datetime%' OR Type LIKE '%timestamp%' OR Type LIKE '%date%'");
        $timestampColumns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($timestampColumns)) {
            foreach ($timestampColumns as $column) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` IS NULL");
                $stmt->execute();
                $nullCount = $stmt->fetchColumn();
                
                if ($nullCount > 0) {
                    $hasNullValues = true;
                    $output->writeln("  <comment>Table '$table' has $nullCount NULL values in '$column'. Fixing...</comment>");
                    
                    // Update NULL values to current timestamp
                    $pdo->exec("UPDATE `$table` SET `$column` = NOW() WHERE `$column` IS NULL");
                    $output->writeln("  <info>Updated $nullCount rows</info>");
                }
            }
        }
        
        if (!$hasNullValues && !empty($timestampColumns)) {
            $output->writeln("  Table '$table' has no NULL timestamp values.");
        }
    } catch (PDOException $e) {
        $output->writeln("  <comment>Could not check NULL values in table '$table': " . $e->getMessage() . "</comment>");
    }
}

$output->writeln("\n<info>Entity timestamp fix complete!</info>");
$output->writeln("You should now be able to run 'php bin/console doctrine:schema:update --force' without errors.");
