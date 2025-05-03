<?php
/**
 * Script to add missing 'purpose' column to image table
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

$io = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
$io->title('Adding missing purpose column to image table');

// Parse the database URL
$databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
if (!$databaseUrl) {
    $io->error("Error: DATABASE_URL environment variable not found.");
    exit(1);
}

$params = parse_url($databaseUrl);
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

try {
    // Connect to database
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $io->success("Connected to database $dbName");
    
    // Check if image table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'image'")->rowCount() > 0;
    
    if (!$tableExists) {
        $io->error("The 'image' table doesn't exist.");
        exit(1);
    }
    
    // Check if purpose column already exists
    $stmt = $pdo->query("DESCRIBE `image`");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    
    if (in_array('purpose', $columns)) {
        $io->info("The 'purpose' column already exists in the 'image' table.");
        exit(0);
    }
    
    // Add purpose column to image table
    $io->section('Adding purpose column');
    $pdo->exec("ALTER TABLE `image` ADD `purpose` VARCHAR(64) DEFAULT NULL AFTER `category`");
    $io->success("Added 'purpose' column to 'image' table");
    
} catch (PDOException $e) {
    $io->error("Database error: " . $e->getMessage());
    exit(1);
}

$io->success('Column addition completed successfully!');
