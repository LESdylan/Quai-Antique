<?php
/**
 * Direct Schema Update Script for Quai Antique
 * 
 * This script updates the database schema directly using SQL statements,
 * bypassing Doctrine's schema tools which cause the "Unknown column 'i_c.TABLE_NAME'" error.
 * 
 * Usage: php bin/direct_schema_update.php [--dump-sql] [--force]
 */

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArgvInput;

$output = new ConsoleOutput();
$input = new ArgvInput();
$io = new SymfonyStyle($input, $output);

$io->title('Direct Schema Update Tool');

// Parse command line arguments
$dumpSql = in_array('--dump-sql', $argv);
$force = in_array('--force', $argv);

if (!$dumpSql && !$force) {
    $io->error('You must specify either --dump-sql to view the SQL or --force to execute it');
    exit(1);
}

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

// Get database connection parameters
$dbUrl = $_ENV['DATABASE_URL'] ?? null;
if (!$dbUrl) {
    $io->error('DATABASE_URL not found in environment variables');
    exit(1);
}

// Parse database URL
$dbParams = parse_url($dbUrl);
if ($dbParams === false) {
    $io->error('Invalid DATABASE_URL format');
    exit(1);
}

$dbHost = $dbParams['host'] ?? 'localhost';
$dbPort = $dbParams['port'] ?? 3306;
$dbName = ltrim($dbParams['path'] ?? '', '/');
$dbUser = $dbParams['user'] ?? null;
$dbPass = $dbParams['pass'] ?? null;

// Handle query parameters in database name
if (strpos($dbName, '?') !== false) {
    $dbName = substr($dbName, 0, strpos($dbName, '?'));
}

$io->section('Database Connection');
$io->table(
    ['Parameter', 'Value'],
    [
        ['Host', $dbHost],
        ['Port', $dbPort],
        ['Database', $dbName],
        ['User', $dbUser],
        ['Password', str_repeat('*', strlen($dbPass ?? ''))]
    ]
);

// Connect to database
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $io->success("Connected to database $dbName");
} catch (PDOException $e) {
    $io->error('Database connection failed: ' . $e->getMessage());
    exit(1);
}

// Define table definitions based on our entities
$io->section('Defining Table Structures');

$tables = [
    'user' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'email' => 'VARCHAR(180) NOT NULL',
        'roles' => 'JSON NOT NULL',
        'password' => 'VARCHAR(255) NOT NULL',
        'first_name' => 'VARCHAR(64) NULL',
        'last_name' => 'VARCHAR(64) NULL',
        'created_at' => 'DATETIME NOT NULL',
        'updated_at' => 'DATETIME NOT NULL'
    ],
    'reservation' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'date' => 'DATETIME NOT NULL',
        'guest_count' => 'INT NOT NULL',
        'last_name' => 'VARCHAR(64) NOT NULL',
        'first_name' => 'VARCHAR(64) NULL',
        'email' => 'VARCHAR(180) NOT NULL',
        'phone' => 'VARCHAR(20) NOT NULL',
        'status' => 'VARCHAR(20) NOT NULL',
        'notes' => 'TEXT NULL',
        'allergies' => 'TEXT NULL', 
        'created_at' => 'DATETIME NOT NULL',
        'user_id' => 'INT NULL'
    ],
    'category' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(64) NOT NULL',
        'description' => 'TEXT NULL',
        'position' => 'INT DEFAULT 0'
    ],
    'dish' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(64) NOT NULL',
        'description' => 'TEXT NULL',
        'price' => 'DECIMAL(10,2) NOT NULL',
        'image' => 'VARCHAR(255) NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'created_at' => 'DATETIME NOT NULL',
        'updated_at' => 'DATETIME NOT NULL',
        'category_id' => 'INT NULL',
        'is_seasonal' => 'TINYINT(1) NULL',
        'popularity_score' => 'INT NULL'
    ],
    'hours' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'day_of_week' => 'INT NOT NULL',
        'lunch_opening_time' => 'TIME NULL',
        'lunch_closing_time' => 'TIME NULL',
        'dinner_opening_time' => 'TIME NULL',
        'dinner_closing_time' => 'TIME NULL',
        'is_closed' => 'TINYINT(1) NOT NULL DEFAULT 0'
    ],
    'allergen' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(64) NOT NULL',
        'description' => 'TEXT NULL'
    ],
    'dish_allergen' => [
        'dish_id' => 'INT NOT NULL',
        'allergen_id' => 'INT NOT NULL',
        'PRIMARY KEY' => '(dish_id, allergen_id)'
    ],
    'menu' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'title' => 'VARCHAR(64) NOT NULL',
        'description' => 'TEXT NULL',
        'price' => 'DECIMAL(10,2) NOT NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'start_date' => 'DATETIME NULL',
        'end_date' => 'DATETIME NULL'
    ],
    'menu_dish' => [
        'menu_id' => 'INT NOT NULL',
        'dish_id' => 'INT NOT NULL',
        'PRIMARY KEY' => '(menu_id, dish_id)'
    ],
    'restaurant_table' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'number' => 'INT NOT NULL',
        'seats' => 'INT NOT NULL',
        'location' => 'VARCHAR(64) NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1'
    ],
    'image' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'filename' => 'VARCHAR(255) NOT NULL',
        'original_filename' => 'VARCHAR(255) NULL',
        'mime_type' => 'VARCHAR(255) NULL',
        'alt' => 'VARCHAR(255) NOT NULL',
        'category' => 'VARCHAR(64) NULL',
        'dish_id' => 'INT NULL',
        'created_at' => 'DATETIME NOT NULL',
        'updated_at' => 'DATETIME NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'title' => 'VARCHAR(255) NULL'
    ],
    'gallery' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'title' => 'VARCHAR(64) NOT NULL',
        'filename' => 'VARCHAR(255) NOT NULL',
        'description' => 'TEXT NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'position' => 'INT NOT NULL DEFAULT 0',
        'created_at' => 'DATETIME NOT NULL'
    ]
];

// Foreign key constraints to add
$foreignKeys = [
    'reservation' => [
        'user_id' => ['user', 'id', 'SET NULL']
    ],
    'dish' => [
        'category_id' => ['category', 'id', 'SET NULL']
    ],
    'image' => [
        'dish_id' => ['dish', 'id', 'SET NULL']
    ],
    'dish_allergen' => [
        'dish_id' => ['dish', 'id', 'CASCADE'],
        'allergen_id' => ['allergen', 'id', 'CASCADE']
    ],
    'menu_dish' => [
        'menu_id' => ['menu', 'id', 'CASCADE'],
        'dish_id' => ['dish', 'id', 'CASCADE']
    ]
];

// Generate and collect SQL statements
$io->section('Generating SQL Statements');
$sqlStatements = [];

// For each defined table
foreach ($tables as $tableName => $columns) {
    // Check if table exists
    $tableExists = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $io->error("Error checking if table $tableName exists: " . $e->getMessage());
        continue;
    }

    if (!$tableExists) {
        // Table doesn't exist, create it
        $io->text("Table '$tableName' doesn't exist, will create it");

        $columnDefs = [];
        foreach ($columns as $columnName => $definition) {
            $columnDefs[] = "`$columnName` $definition";
        }
        
        $sql = "CREATE TABLE `$tableName` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $sqlStatements[] = $sql;
    } else {
        // Table exists, check if columns need to be added
        $io->text("Table '$tableName' exists, checking columns");
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName`");
            $existingColumns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[] = $row['Field'];
            }
            
            foreach ($columns as $columnName => $definition) {
                if ($columnName === 'PRIMARY KEY') continue;
                
                if (!in_array($columnName, $existingColumns)) {
                    $io->text("Column '$columnName' missing in table '$tableName'");
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $definition";
                    $sqlStatements[] = $sql;
                }
            }
        } catch (PDOException $e) {
            $io->error("Error checking columns for table $tableName: " . $e->getMessage());
        }
    }
}

// Add foreign key constraints if needed
foreach ($foreignKeys as $tableName => $keys) {
    // Check if table exists first
    $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
    if ($stmt->rowCount() === 0) {
        $io->warning("Table '$tableName' doesn't exist, skipping foreign keys");
        continue;
    }
    
    foreach ($keys as $columnName => $reference) {
        list($refTable, $refColumn, $onDelete) = $reference;
        
        // Check if the referenced table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$refTable'");
        if ($stmt->rowCount() === 0) {
            $io->warning("Referenced table '$refTable' doesn't exist, skipping foreign key");
            continue;
        }
        
        // Check if the constraint already exists
        $constraintName = "fk_{$tableName}_{$columnName}";
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = '$tableName'
            AND CONSTRAINT_NAME = '$constraintName'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $constraintExists = (int)$stmt->fetchColumn() > 0;
        
        if (!$constraintExists) {
            $io->text("Foreign key constraint '$constraintName' missing, will add it");
            $sql = "ALTER TABLE `$tableName` ADD CONSTRAINT `$constraintName` 
                    FOREIGN KEY (`$columnName`) REFERENCES `$refTable` (`$refColumn`)
                    ON DELETE $onDelete";
            $sqlStatements[] = $sql;
        }
    }
}

// Display or execute SQL statements
if (empty($sqlStatements)) {
    $io->success("No schema changes required. Database schema is up to date.");
    exit(0);
}

$io->section("SQL Statements (" . count($sqlStatements) . ")");

if ($dumpSql) {
    foreach ($sqlStatements as $index => $sql) {
        $io->text("-- Statement " . ($index + 1));
        $io->text($sql . ";\n");
    }
    $io->note("Use --force to execute these statements");
}

if ($force) {
    $io->section("Executing SQL Statements");
    $pdo->beginTransaction();
    $success = true;
    
    try {
        foreach ($sqlStatements as $index => $sql) {
            $io->text("Executing statement " . ($index + 1) . "...");
            try {
                $pdo->exec($sql);
                $io->text("✓ Success");
            } catch (PDOException $e) {
                $io->error("Error: " . $e->getMessage());
                $io->text("  SQL: $sql");
                $success = false;
                // Continue with other statements
            }
        }
        
        if ($success) {
            $pdo->commit();
            $io->success("Schema update completed successfully!");
        } else {
            $pdo->rollBack();
            $io->error("Schema update failed! Please check the errors above.");
            exit(1);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $io->error("Fatal error during schema update: " . $e->getMessage());
        exit(1);
    }
}

$io->text("Done!");
