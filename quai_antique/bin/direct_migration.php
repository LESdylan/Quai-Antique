<?php
/**
 * Direct Migration Script for Quai Antique
 * 
 * This script bypasses Doctrine's schema tools completely and
 * directly creates/updates tables based on entity definitions.
 */

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Dotenv\Dotenv;

// Setup output formatting
$input = new ArgvInput();
$output = new ConsoleOutput();
$io = new SymfonyStyle($input, $output);

$io->title('Direct Schema Migration Tool');

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);

if (!$force && !$dryRun) {
    $io->error('You must specify either --dry-run to view the SQL or --force to execute it');
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
$io->section('Defining Entity Schemas');

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
    'Reservation' => [
        'table' => 'reservation',
        'columns' => [
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
        ]
    ],
    'Category' => [
        'table' => 'category',
        'columns' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(64) NOT NULL',
            'description' => 'TEXT NULL',
            'position' => 'INT DEFAULT 0'
        ]
    ],
    'Dish' => [
        'table' => 'dish',
        'columns' => [
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
        ]
    ],
    'Hours' => [
        'table' => 'hours',
        'columns' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'day_of_week' => 'INT NOT NULL',
            'lunch_opening_time' => 'TIME NULL',
            'lunch_closing_time' => 'TIME NULL',
            'dinner_opening_time' => 'TIME NULL',
            'dinner_closing_time' => 'TIME NULL',
            'is_closed' => 'TINYINT(1) NOT NULL DEFAULT 0'
        ]
    ],
    'Restaurant' => [
        'table' => 'restaurant',
        'columns' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'name' => 'VARCHAR(255) NOT NULL',
            'description' => 'TEXT NULL',
            'address' => 'VARCHAR(255) NULL',
            'phone' => 'VARCHAR(20) NULL',
            'email' => 'VARCHAR(180) NULL',
            'max_guests' => 'INT NULL'
        ]
    ],
    'Menu' => [
        'table' => 'menu',
        'columns' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'title' => 'VARCHAR(64) NOT NULL',
            'description' => 'TEXT NULL',
            'price' => 'DECIMAL(10,2) NOT NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'start_date' => 'DATETIME NULL',
            'end_date' => 'DATETIME NULL'
        ]
    ],
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
            'title' => 'VARCHAR(255) NULL'
        ]
    ]
];

// Define relationship tables
$relationTables = [
    'menu_dish' => [
        'columns' => [
            'menu_id' => 'INT NOT NULL',
            'dish_id' => 'INT NOT NULL'
        ],
        'primary_key' => ['menu_id', 'dish_id'],
        'foreign_keys' => [
            'menu_id' => ['table' => 'menu', 'column' => 'id', 'on_delete' => 'CASCADE'],
            'dish_id' => ['table' => 'dish', 'column' => 'id', 'on_delete' => 'CASCADE']
        ]
    ],
];

// Generate SQL statements
$io->section('Generating SQL Statements');
$sqlStatements = [];

// First check all tables
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
                if (!in_array($columnName, $existingColumns)) {
                    $io->text("Column '$columnName' missing in table '$tableName'");
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnDef";
                    $sqlStatements[] = $sql;
                }
            }
        } else {
            $io->text("Table '$tableName' doesn't exist, will create it");
            
            // Create table SQL
            $createTableSQL = "CREATE TABLE `$tableName` (\n";
            foreach ($columns as $columnName => $columnDef) {
                $createTableSQL .= "  `$columnName` $columnDef,\n";
            }
            // Remove trailing comma
            $createTableSQL = rtrim($createTableSQL, ",\n") . "\n";
            $createTableSQL .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $sqlStatements[] = $createTableSQL;
        }
    } catch (PDOException $e) {
        $io->warning("Error checking table '$tableName': " . $e->getMessage());
    }
}

// Check relationship tables
foreach ($relationTables as $tableName => $tableInfo) {
    $columns = $tableInfo['columns'];
    $primaryKey = $tableInfo['primary_key'];
    $foreignKeys = $tableInfo['foreign_keys'] ?? [];
    
    // Check if table exists
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
        
        if (!$tableExists) {
            $io->text("Relationship table '$tableName' doesn't exist, will create it");
            
            // Create table SQL
            $createTableSQL = "CREATE TABLE `$tableName` (\n";
            foreach ($columns as $columnName => $columnDef) {
                $createTableSQL .= "  `$columnName` $columnDef,\n";
            }
            
            // Add primary key
            if (!empty($primaryKey)) {
                $primaryKeySQL = implode("`, `", $primaryKey);
                $createTableSQL .= "  PRIMARY KEY (`$primaryKeySQL`),\n";
            }
            
            // Add foreign keys
            foreach ($foreignKeys as $column => $fkInfo) {
                $createTableSQL .= "  CONSTRAINT `FK_{$tableName}_{$column}` FOREIGN KEY (`$column`) ";
                $createTableSQL .= "REFERENCES `{$fkInfo['table']}` (`{$fkInfo['column']}`) ";
                $createTableSQL .= "ON DELETE {$fkInfo['on_delete']},\n";
            }
            
            // Remove trailing comma
            $createTableSQL = rtrim($createTableSQL, ",\n") . "\n";
            $createTableSQL .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $sqlStatements[] = $createTableSQL;
        } else {
            $io->text("Relationship table '$tableName' exists, checking structure");
            
            // Check if primary key is correct
            // This would require more complex queries to check and fix, skipping for now
            
            // Check for foreign key constraints
            try {
                $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $createTableStatement = $row['Create Table'] ?? '';
                
                foreach ($foreignKeys as $column => $fkInfo) {
                    $constraintName = "FK_{$tableName}_{$column}";
                    if (strpos($createTableStatement, $constraintName) === false) {
                        $io->text("Missing foreign key constraint for '$column' in table '$tableName'");
                        
                        $sql = "ALTER TABLE `$tableName` ADD CONSTRAINT `$constraintName` ";
                        $sql .= "FOREIGN KEY (`$column`) REFERENCES `{$fkInfo['table']}` (`{$fkInfo['column']}`) ";
                        $sql .= "ON DELETE {$fkInfo['on_delete']}";
                        
                        $sqlStatements[] = $sql;
                    }
                }
            } catch (PDOException $e) {
                $io->warning("Error checking constraints for '$tableName': " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        $io->warning("Error checking relationship table '$tableName': " . $e->getMessage());
    }
}

// Also add foreign keys for the regular tables
$foreignKeys = [
    'reservation' => [
        'user_id' => ['table' => 'user', 'column' => 'id', 'on_delete' => 'SET NULL']
    ],
    'dish' => [
        'category_id' => ['table' => 'category', 'column' => 'id', 'on_delete' => 'SET NULL']
    ],
    'image' => [
        'dish_id' => ['table' => 'dish', 'column' => 'id', 'on_delete' => 'SET NULL']
    ]
];

foreach ($foreignKeys as $tableName => $tableKeys) {
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
        if (!$tableExists) {
            continue; // Skip if table doesn't exist yet
        }
        
        // Get existing constraints for this table to avoid duplicates
        $existingConstraints = [];
        try {
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = '$dbName' 
                AND TABLE_NAME = '$tableName'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingConstraints[] = $row['CONSTRAINT_NAME'];
            }
        } catch (PDOException $e) {
            // If this query fails, just continue with an empty constraint list
            $io->warning("Could not check existing constraints: " . $e->getMessage());
        }
        
        $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $createTableStatement = $row['Create Table'] ?? '';
        
        foreach ($tableKeys as $column => $fkInfo) {
            // Use a more unique constraint name with a timestamp suffix to avoid duplicates
            $constraintName = "FK_{$tableName}_{$column}_" . time();
            
            // Check if a constraint already exists for this column
            $hasConstraint = false;
            foreach ($existingConstraints as $existingConstraint) {
                if (strpos($existingConstraint, "FK_{$tableName}_{$column}") === 0) {
                    $hasConstraint = true;
                    break;
                }
            }
            
            // Also check in the CREATE TABLE statement as a fallback
            if (!$hasConstraint && strpos($createTableStatement, "FOREIGN KEY (`$column`)") !== false) {
                $hasConstraint = true;
            }
            
            if (!$hasConstraint) {
                $io->text("Missing foreign key constraint for '$column' in table '$tableName'");
                
                $sql = "ALTER TABLE `$tableName` ADD CONSTRAINT `$constraintName` ";
                $sql .= "FOREIGN KEY (`$column`) REFERENCES `{$fkInfo['table']}` (`{$fkInfo['column']}`) ";
                $sql .= "ON DELETE {$fkInfo['on_delete']}";
                
                $sqlStatements[] = $sql;
            } else {
                $io->text("Foreign key constraint for '$column' in table '$tableName' already exists");
            }
        }
    } catch (PDOException $e) {
        $io->warning("Error checking foreign keys for '$tableName': " . $e->getMessage());
    }
}

// Display or execute SQL statements
if (empty($sqlStatements)) {
    $io->success("No schema changes required. Database schema is up to date.");
    exit(0);
}

$io->section("SQL Statements (" . count($sqlStatements) . ")");

foreach ($sqlStatements as $index => $sql) {
    $io->text("-- Statement " . ($index + 1));
    $io->text($sql . ";\n");
}

if ($dryRun) {
    $io->note("This was a dry run. Run with --force to execute these SQL statements.");
    exit(0);
}

if ($force) {
    $io->section("Executing SQL Statements");
    
    // Execute each statement individually without transaction
    // This way if one fails, others can still succeed
    foreach ($sqlStatements as $index => $sql) {
        $io->text("Executing statement " . ($index + 1) . "...");
        try {
            $pdo->exec($sql);
            $io->success("Statement " . ($index + 1) . " executed successfully");
        } catch (PDOException $e) {
            $io->error("Error executing statement " . ($index + 1) . ": " . $e->getMessage());
            $io->text("  SQL: $sql");
            // Continue with next statement instead of stopping
        }
    }
    
    $io->success("Schema update completed with some statements possibly failing.");
    $io->note("Review any errors above and run the script again if needed.");
}

$io->text("Done!");
