<?php
/**
 * Direct Reservation Table Structure Update Script
 * For fixing database issues when normal Doctrine operations fail
 */

// Bootstrap the application
$projectDir = dirname(__DIR__);
require_once $projectDir . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;

// Load .env files
$dotenv = new Dotenv();
$dotenv->loadEnv($projectDir.'/.env');

// Parse the database URL
$databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
if (!$databaseUrl) {
    echo "Error: DATABASE_URL environment variable not found.\n";
    exit(1);
}

$io = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
$io->title('Reservation Table Structure Update');

// Parse database connection details
$params = parse_url($databaseUrl);
$dbHost = $params['host'] ?? 'localhost';
$dbPort = $params['port'] ?? 3306;
$dbName = ltrim($params['path'] ?? '', '/');
$dbUser = $params['user'] ?? null;
$dbPass = $params['pass'] ?? null;

// Check if parameters were parsed correctly
if (!$dbName || !$dbUser) {
    $io->error('Could not parse DATABASE_URL correctly');
    exit(1);
}

$io->section('Database Connection Details');
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

try {
    // Create database connection
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $io->success("Connected to database successfully");
    
    // Check if reservation table exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName' AND table_name = 'reservation'");
    $tableExists = (int) $stmt->fetchColumn();
    
    if (!$tableExists) {
        $io->section('Creating Reservation Table');
        
        $createTableSQL = "
            CREATE TABLE `reservation` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `date` DATETIME NOT NULL,
                `guest_count` INT NOT NULL,
                `last_name` VARCHAR(64) NOT NULL,
                `first_name` VARCHAR(64) NULL,
                `email` VARCHAR(180) NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `notes` TEXT NULL,
                `allergies` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `user_id` INT NULL
            )
        ";
        
        $pdo->exec($createTableSQL);
        $io->success('Created reservation table');
        
        // Check if user table exists to add foreign key
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName' AND table_name = 'user'");
        $userTableExists = (int) $stmt->fetchColumn();
        
        if ($userTableExists) {
            $io->text('Adding foreign key constraint for user_id...');
            try {
                $pdo->exec("
                    ALTER TABLE `reservation` 
                    ADD CONSTRAINT `FK_reservation_user` 
                    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
                ");
                $io->success('Added foreign key constraint');
            } catch (\PDOException $e) {
                $io->warning('Could not add foreign key constraint: ' . $e->getMessage());
            }
        }
    } else {
        $io->section('Checking Missing Columns');
        
        // Define required columns
        $requiredColumns = [
            'last_name' => 'VARCHAR(64) NOT NULL',
            'first_name' => 'VARCHAR(64) NULL',
            'email' => 'VARCHAR(180) NOT NULL',
            'phone' => 'VARCHAR(20) NOT NULL',
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'notes' => 'TEXT NULL',
            'allergies' => 'TEXT NULL',
            'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'user_id' => 'INT NULL'
        ];
        
        // Get existing columns
        $stmt = $pdo->query("SHOW COLUMNS FROM reservation");
        $existingColumns = [];
        while ($row = $stmt->fetch()) {
            $existingColumns[] = $row['Field'];
        }
        
        foreach ($requiredColumns as $columnName => $columnDef) {
            if (!in_array($columnName, $existingColumns)) {
                $io->text("Adding missing column: $columnName");
                try {
                    $pdo->exec("ALTER TABLE `reservation` ADD COLUMN `$columnName` $columnDef");
                    $io->success("Added column $columnName");
                } catch (\PDOException $e) {
                    $io->error("Failed to add column $columnName: " . $e->getMessage());
                }
            } else {
                $io->text("Column $columnName already exists ✓");
            }
        }
        
        // Check if foreign key exists
        if (in_array('user_id', $existingColumns)) {
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '$dbName' 
                  AND TABLE_NAME = 'reservation' 
                  AND COLUMN_NAME = 'user_id' 
                  AND REFERENCED_TABLE_NAME = 'user'
            ");
            $fkExists = (int) $stmt->fetchColumn();
            
            if (!$fkExists) {
                $io->text('User ID column exists but has no foreign key constraint');
                
                // Check if user table exists
                $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName' AND table_name = 'user'");
                $userTableExists = (int) $stmt->fetchColumn();
                
                if ($userTableExists) {
                    $io->text('Adding foreign key constraint...');
                    try {
                        $pdo->exec("
                            ALTER TABLE `reservation` 
                            ADD CONSTRAINT `FK_reservation_user` 
                            FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
                        ");
                        $io->success('Added foreign key constraint');
                    } catch (\PDOException $e) {
                        $io->warning('Could not add foreign key constraint: ' . $e->getMessage());
                    }
                } else {
                    $io->warning('User table does not exist, cannot add foreign key constraint');
                }
            }
        }
    }
    
    $io->section('Database Update Complete');
    $io->success('The reservation table has been updated with all required columns.');
    $io->text('Run "php bin/console app:schema:validate" to verify the schema is valid.');
    
} catch (\PDOException $e) {
    $io->error('Database connection failed: ' . $e->getMessage());
    exit(1);
}
