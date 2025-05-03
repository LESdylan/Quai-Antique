<?php
/**
 * Script to fix the Reservation schema by adding missing columns
 */

// Determine project root directory
$projectDir = dirname(__DIR__);

// Check if direct SQL approach should be used
$direct = in_array('--direct', $argv);

if ($direct) {
    echo "Using direct SQL approach to fix the schema\n";
    
    try {
        // Load Symfony .env file to get database configuration
        $envFile = $projectDir . '/.env.local';
        if (!file_exists($envFile)) {
            $envFile = $projectDir . '/.env';
        }
        
        $envContent = file_get_contents($envFile);
        if (preg_match('/DATABASE_URL=(.+)/', $envContent, $matches)) {
            $databaseUrl = trim($matches[1], "'\"");
            
            // Parse the database URL
            if (preg_match('/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/', $databaseUrl, $dbMatches)) {
                $dbUser = $dbMatches[1];
                $dbPass = $dbMatches[2];
                $dbHost = $dbMatches[3];
                $dbPort = $dbMatches[4];
                $dbName = $dbMatches[5];
                
                // Remove query parameters if any
                if (($pos = strpos($dbName, '?')) !== false) {
                    $dbName = substr($dbName, 0, $pos);
                }
                
                echo "Connecting to database $dbName on $dbHost:$dbPort as $dbUser\n";
                
                // Connect to the database
                $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if reservation table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'reservation'");
                if ($stmt->rowCount() === 0) {
                    echo "Error: Reservation table does not exist. Create it first.\n";
                    exit(1);
                }
                
                // Check if date column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE 'date'");
                if ($stmt->rowCount() === 0) {
                    echo "Adding 'date' column to reservation table...\n";
                    $pdo->exec("ALTER TABLE `reservation` ADD `date` DATETIME NOT NULL");
                    echo "Column 'date' added successfully.\n";
                } else {
                    echo "Column 'date' already exists in reservation table.\n";
                }
                
                // Check if other essential columns exist and add them if needed
                $requiredColumns = [
                    'guest_count' => "ALTER TABLE `reservation` ADD `guest_count` INT NOT NULL",
                    'email' => "ALTER TABLE `reservation` ADD `email` VARCHAR(180) NOT NULL",
                    'phone' => "ALTER TABLE `reservation` ADD `phone` VARCHAR(20) NOT NULL",
                    'status' => "ALTER TABLE `reservation` ADD `status` VARCHAR(20) NOT NULL",
                    'notes' => "ALTER TABLE `reservation` ADD `notes` TEXT NULL",
                    'allergies' => "ALTER TABLE `reservation` ADD `allergies` TEXT NULL",
                    'created_at' => "ALTER TABLE `reservation` ADD `created_at` DATETIME NOT NULL"
                ];
                
                foreach ($requiredColumns as $column => $sql) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM `reservation` LIKE '$column'");
                    if ($stmt->rowCount() === 0) {
                        echo "Adding '$column' column to reservation table...\n";
                        $pdo->exec($sql);
                        echo "Column '$column' added successfully.\n";
                    } else {
                        echo "Column '$column' already exists in reservation table.\n";
                    }
                }
                
                echo "Schema update completed successfully!\n";
            } else {
                echo "Error: Could not parse DATABASE_URL from .env file\n";
                exit(1);
            }
        } else {
            echo "Error: DATABASE_URL not found in .env file\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Using Doctrine Schema Manager to fix the schema\n";
    
    // Execute schema update command
    echo "Running doctrine:schema:update...\n";
    $command = "php $projectDir/bin/console doctrine:schema:update --force";
    $output = [];
    exec($command, $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    
    if ($returnCode !== 0) {
        echo "Error: Schema update failed with code $returnCode\n";
        echo "Try using the direct approach with --direct parameter\n";
        exit(1);
    }
    
    echo "Schema update completed successfully!\n";
}

echo "\nTo verify the fix, run: php bin/console doctrine:schema:validate\n";
