#!/usr/bin/env php
<?php
// This script adds the missing focal_point column to the image table

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Database connection settings
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = 'root';  // Using provided credentials from previous examples
$dbPass = 'MO3848seven_36';  // Using provided credentials from previous examples
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

// Connect to the database
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔌 Connected to database successfully\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Add missing columns to the image table
try {
    // Check if columns exist
    $columnsToAdd = [
        'focal_point' => "ALTER TABLE image ADD focal_point VARCHAR(255) DEFAULT '50% 50%'",
        'file_size' => "ALTER TABLE image ADD file_size INT DEFAULT NULL", 
        'dimensions' => "ALTER TABLE image ADD dimensions VARCHAR(255) DEFAULT NULL"
    ];
    
    foreach ($columnsToAdd as $columnName => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM image LIKE '$columnName'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            echo "🛠️ Adding '$columnName' column to the image table...\n";
            $pdo->exec($sql);
            echo "✅ Column '$columnName' added successfully\n";
        } else {
            echo "✅ Column '$columnName' already exists\n";
        }
    }
    
    // Check for tags table and create it if needed
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'tag'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "🛠️ Creating 'tag' table...\n";
            $sql = "
                CREATE TABLE tag (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(64) NOT NULL,
                    slug VARCHAR(64) NOT NULL,
                    color VARCHAR(7) DEFAULT NULL,
                    PRIMARY KEY(id),
                    UNIQUE KEY(slug)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ";
            $pdo->exec($sql);
            echo "✅ Table 'tag' created successfully\n";
        }
    } catch (PDOException $e) {
        echo "⚠️ Error checking/creating tag table: " . $e->getMessage() . "\n";
    }
    
    // Check for image_tag table and create it if needed
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'image_tag'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "🛠️ Creating 'image_tag' table...\n";
            $sql = "
                CREATE TABLE image_tag (
                    image_id INT NOT NULL,
                    tag_id INT NOT NULL,
                    PRIMARY KEY(image_id, tag_id),
                    INDEX IDX_5B6367D03DA5256D (image_id),
                    INDEX IDX_5B6367D0BAD26311 (tag_id),
                    CONSTRAINT FK_5B6367D03DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE,
                    CONSTRAINT FK_5B6367D0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ";
            $pdo->exec($sql);
            echo "✅ Table 'image_tag' created successfully\n";
        }
    } catch (PDOException $e) {
        echo "⚠️ Error checking/creating image_tag table: " . $e->getMessage() . "\n";
    }
    
    echo "✨ Database update completed successfully!\n";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
