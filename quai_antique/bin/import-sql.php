<?php
/**
 * Direct SQL Import Script
 * 
 * This script imports an SQL file directly into the database using PDO
 * Usage: php bin/import-sql.php <sql-file>
 */

// Check arguments
if ($argc < 2) {
    echo "Usage: php import-sql.php <sql-file>\n";
    exit(1);
}

// Get SQL file path
$sqlFile = $argv[1];
if (!file_exists($sqlFile)) {
    echo "Error: SQL file not found: $sqlFile\n";
    exit(1);
}

// Get database connection details from Symfony
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Use Symfony's Dotenv component instead of parse_ini_file
use Symfony\Component\Dotenv\Dotenv;

// Try to load environment variables
try {
    $dotenv = new Dotenv();
    $dotenv->loadEnv(dirname(__DIR__).'/.env');
    $dbUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
    
    if (!$dbUrl) {
        throw new \Exception("DATABASE_URL not found in environment");
    }
} catch (\Exception $e) {
    // If Dotenv fails, fall back to direct database details
    echo "Warning: Could not load environment variables: " . $e->getMessage() . "\n";
    echo "Using hardcoded database connection details instead.\n";
    
    // Hardcoded fallback for the restaurant database
    $dbUrl = "mysql://root:MO3848seven_36@localhost:3306/sf_restaurant?serverVersion=10.11.2-MariaDB&charset=utf8mb4";
}

// Parse database URL - improved regex for better handling of special characters
$matches = [];
if (preg_match('/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/', $dbUrl, $matches)) {
    $username = urldecode($matches[1]);
    $password = urldecode($matches[2]);
    $host = $matches[3];
    $port = $matches[4];
    $dbname = $matches[5];
    
    // Extract dbname without query parameters
    if (($pos = strpos($dbname, '?')) !== false) {
        $dbname = substr($dbname, 0, $pos);
    }
} else {
    echo "Error: Could not parse DATABASE_URL: $dbUrl\n";
    exit(1);
}

try {
    // Connect to database
    echo "Connecting to database $dbname on $host:$port as $username...\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Read SQL file content
    echo "Importing SQL file: $sqlFile\n";
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements, preserving comments
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if (empty($trimmedLine) || strpos($trimmedLine, '--') === 0) {
            // Skip comments and empty lines when building statements
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // If this line has a semicolon at the end, it's the end of a statement
        if (substr(trim($line), -1) === ';') {
            $statements[] = $currentStatement;
            $currentStatement = '';
        }
    }
    
    // Add the last statement if there's any
    if (!empty(trim($currentStatement))) {
        $statements[] = $currentStatement;
    }
    
    // Execute each statement separately
    $pdo->beginTransaction();
    
    $count = 0;
    $totalStatements = count($statements);
    echo "Found $totalStatements SQL statements to execute\n";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            echo "Executing statement " . (++$count) . "/$totalStatements...\n";
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    echo "Import complete! Executed $count SQL statements successfully.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit(1);
}
