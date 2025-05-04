<?php

/**
 * MENU TABLE REPAIR SCRIPT
 * 
 * This script directly fixes the 'meal_type' column in the menu table
 * to prevent NULL values and set a default value.
 */

// Load database configuration from .env files
$envFile = __DIR__ . '/.env';
$envLocalFile = __DIR__ . '/.env.local';
$databaseUrl = null;

// Read DATABASE_URL from .env.local first, then .env
if (file_exists($envLocalFile)) {
    $envContent = file_get_contents($envLocalFile);
    if (preg_match('/DATABASE_URL="([^"]+)"/', $envContent, $matches)) {
        $databaseUrl = $matches[1];
    }
}

if (!$databaseUrl && file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/DATABASE_URL="([^"]+)"/', $envContent, $matches)) {
        $databaseUrl = $matches[1];
    }
}

if (!$databaseUrl) {
    echo "Error: Could not find DATABASE_URL in .env or .env.local\n";
    exit(1);
}

// Parse the database URL
$dbParams = parse_url($databaseUrl);
$dbUser = $dbParams['user'] ?? null;
$dbPass = $dbParams['pass'] ?? null;
$dbHost = $dbParams['host'] ?? null;
$dbPort = $dbParams['port'] ?? '3306';
$dbName = ltrim($dbParams['path'] ?? '', '/');

echo "=======================================\n";
echo "MENU TABLE REPAIR SCRIPT\n";
echo "=======================================\n";
echo "Host: $dbHost:$dbPort\n";
echo "User: $dbUser\n";
echo "Database: $dbName\n\n";

// Connect to the database
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connected to database successfully\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 1: Check if the table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'menu'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Table 'menu' does not exist. Did you run migrations?\n";
        exit(1);
    }
    echo "✅ Found 'menu' table\n";
} catch (PDOException $e) {
    echo "❌ Error checking table existence: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 2: Check if the column exists
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM menu LIKE 'meal_type'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "❗ Column 'meal_type' doesn't exist. Creating it now...\n";
        $pdo->exec("ALTER TABLE menu ADD COLUMN meal_type VARCHAR(255) NOT NULL DEFAULT 'main'");
        echo "✅ Added 'meal_type' column with NOT NULL constraint and DEFAULT value\n";
    } else {
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Found 'meal_type' column with type: " . $column['Type'] . "\n";
        
        // Check if NULL values are currently allowed
        $nullAllowed = strtoupper($column['Null']) === 'YES';
        if ($nullAllowed) {
            echo "❗ Column 'meal_type' currently allows NULL values. Fixing...\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Error checking column: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 3: Fix any NULL values
try {
    $rowsUpdated = $pdo->exec("UPDATE menu SET meal_type = 'main' WHERE meal_type IS NULL OR meal_type = ''");
    echo "✅ Fixed $rowsUpdated rows with NULL/empty meal_type values\n";
} catch (PDOException $e) {
    echo "❌ Error updating NULL values: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 4: Add NOT NULL constraint and DEFAULT value
try {
    $pdo->exec("ALTER TABLE menu MODIFY COLUMN meal_type VARCHAR(255) NOT NULL DEFAULT 'main'");
    echo "✅ Added NOT NULL constraint and DEFAULT value to meal_type column\n";
} catch (PDOException $e) {
    echo "❌ Error modifying column: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 5: Verify the fix - check for any remaining NULL values
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu WHERE meal_type IS NULL");
    $nullCount = $stmt->fetchColumn();
    
    if ($nullCount > 0) {
        echo "❌ Still found $nullCount NULL values in meal_type column!\n";
    } else {
        echo "✅ No NULL values remain in meal_type column\n";
    }
    
    // Verify column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM menu LIKE 'meal_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $isNotNull = strtoupper($column['Null']) === 'NO';
    $hasDefault = $column['Default'] === 'main';
    
    if ($isNotNull && $hasDefault) {
        echo "✅ Column 'meal_type' is correctly set as NOT NULL with DEFAULT 'main'\n";
    } else {
        echo "❗ Column 'meal_type' settings: NOT NULL = " . ($isNotNull ? 'true' : 'false') . 
             ", DEFAULT = " . ($column['Default'] ?? 'none') . "\n";
    }
} catch (PDOException $e) {
    echo "❌ Error verifying fix: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=======================================\n";
echo "✅ REPAIR COMPLETED SUCCESSFULLY\n";
echo "=======================================\n";
echo "The meal_type column has been fixed and should no longer allow NULL values.\n";
echo "If you still encounter issues, please check your PHP entity classes to ensure\n";
echo "they are setting a default value for meal_type.\n";
