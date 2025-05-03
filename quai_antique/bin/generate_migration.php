<?php
/**
 * Custom Migration Generator for Quai Antique
 * 
 * Generates migration files without using Doctrine's schema comparison tools
 * which have issues with certain MySQL/MariaDB versions.
 * 
 * Usage: php bin/generate_migration.php [--name=MigrationName]
 */

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;

// Setup output formatting
$input = new ArgvInput();
$output = new ConsoleOutput();
$io = new SymfonyStyle($input, $output);

$io->title('Custom Migration Generator Tool');

// Parse command line arguments
$migrationName = 'Migration';
foreach ($argv as $arg) {
    if (strpos($arg, '--name=') === 0) {
        $migrationName = substr($arg, 7);
    }
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
$params = parse_url($dbUrl);
$dbHost = $params['host'] ?? 'localhost';
$dbPort = $params['port'] ?? 3306;
$dbName = ltrim($params['path'] ?? '', '/');
$dbUser = $params['user'] ?? null;
$dbPass = $params['pass'] ?? null;

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

// Define entity-to-table mappings and columns
$io->section('Analyzing Entity Schemas');

// Same entities definition as in direct_migration.php
$entities = [
    'User' => [
        'table' => 'user',
        'columns' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'email' => 'VARCHAR(180) NOT NULL',
            'roles' => 'JSON NOT NULL',
            'password' => 'VARCHAR(255) NOT NULL',
            'first_name' => 'VARCHAR(64) NULL',
            'last_name' => 'VARCHAR(64) NULL',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NOT NULL'
        ]
    ],
    // ... other entities ...
    'Image' => [
        'table' => 'image',
        'columns' => [
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
            'title' => 'VARCHAR(255) NULL',
            'description' => 'TEXT NULL'  // Added description column
        ]
    ]
];

// Generate SQL statements
$io->section('Generating Migration');
$upSqlStatements = [];
$downSqlStatements = [];

// First check all tables and generate migration SQL
foreach ($entities as $entityName => $entityInfo) {
    $tableName = $entityInfo['table'];
    $columns = $entityInfo['columns'];
    
    // Check if table exists
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
        
        if ($tableExists) {
            $io->text("Table '$tableName' exists, checking for missing columns");
            
            // Get existing columns
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $existingColumns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[] = $row['Field'];
            }
            
            // Check for missing columns
            foreach ($columns as $columnName => $columnDef) {
                if ($columnName === 'id') continue; // Skip ID column
                
                if (!in_array($columnName, $existingColumns)) {
                    $io->text("Column '$columnName' missing in table '$tableName'");
                    
                    // UP: Add the column
                    $upSqlStatements[] = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnDef";
                    
                    // DOWN: Drop the column
                    $downSqlStatements[] = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
                }
            }
        } else {
            $io->text("Table '$tableName' doesn't exist, will create it");
            
            // UP: Create the table
            $createTableSQL = "CREATE TABLE `$tableName` (\n";
            foreach ($columns as $columnName => $columnDef) {
                $createTableSQL .= "  `$columnName` $columnDef,\n";
            }
            // Remove trailing comma
            $createTableSQL = rtrim($createTableSQL, ",\n") . "\n";
            $createTableSQL .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $upSqlStatements[] = $createTableSQL;
            
            // DOWN: Drop the table
            $downSqlStatements[] = "DROP TABLE IF EXISTS `$tableName`";
        }
    } catch (PDOException $e) {
        $io->warning("Error checking table '$tableName': " . $e->getMessage());
    }
}

// Create migration file
$io->section('Creating Migration File');

if (empty($upSqlStatements)) {
    $io->warning('No schema changes needed. Skipping migration creation.');
    exit(0);
}

// Ensure migrations directory exists
$migrationsDir = dirname(__DIR__).'/migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
    $io->text("Created migrations directory: $migrationsDir");
}

// Find highest version number
$version = date('YmdHis'); // Default to current timestamp
$finder = new Finder();
$finder->files()->in($migrationsDir)->name('Version*.php')->sortByName();
if ($finder->hasResults()) {
    foreach ($finder as $file) {
        $className = $file->getBasename('.php');
        if (preg_match('/^Version(\d+)$/', $className, $matches)) {
            $fileVersion = $matches[1];
            if ($fileVersion >= $version) {
                $version = $fileVersion + 1;
            }
        }
    }
}

// Create the migration file
$className = 'Version' . $version;
$migrationFile = $migrationsDir . '/' . $className . '.php';

$upSql = implode(";\n\n        ", $upSqlStatements);
$downSql = implode(";\n\n        ", $downSqlStatements);

$migrationContent = <<<EOT
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class $className extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Custom migration generated for $migrationName';
    }

    public function up(Schema \$schema): void
    {
        // This migration was generated with a custom tool
        // because doctrine:migrations:diff has issues with MySQL/MariaDB
        \$this->addSql('$upSql');
    }

    public function down(Schema \$schema): void
    {
        // This migration was generated with a custom tool
        \$this->addSql('$downSql');
    }
}
EOT;

file_put_contents($migrationFile, $migrationContent);
$io->success("Created migration file: $migrationFile");

$io->section('Running the Migration');
$io->text('To run the migration, execute:');
$io->text('php bin/console doctrine:migrations:migrate');

$io->text("\nDone!");
