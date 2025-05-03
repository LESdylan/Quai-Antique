#!/usr/bin/env php
<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Database connection settings from .env
$dbHost = $_SERVER['DATABASE_HOST'] ?? 'localhost';
$dbName = $_SERVER['DATABASE_NAME'] ?? 'sf_restaurant';
$dbUser = $_SERVER['DATABASE_USER'] ?? 'root';
$dbPass = $_SERVER['DATABASE_PASSWORD'] ?? '';
$dbPort = $_SERVER['DATABASE_PORT'] ?? '3306';

echo "Connecting to database: $dbName on $dbHost...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Check if tag table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'tag'");
$tagTableExists = $stmt->rowCount() > 0;

if (!$tagTableExists) {
    echo "Creating tag table...\n";
    $pdo->exec("
        CREATE TABLE tag (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(64) NOT NULL,
            slug VARCHAR(64) NOT NULL,
            color VARCHAR(7) DEFAULT NULL,
            UNIQUE INDEX UNIQ_389B783989D9B62 (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
    ");
    echo "Tag table created successfully!\n";
} else {
    echo "Tag table already exists\n";
}
