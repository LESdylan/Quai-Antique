<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Style\SymfonyStyle;

class SchemaToolHelper
{
    /**
     * Execute SQL directly to create database tables since Doctrine schema:update is failing
     */
    public function createTables(Connection $connection, SymfonyStyle $io): void
    {
        // Drop tables if they exist (in reverse order to avoid foreign key constraints)
        $dropTables = $this->getDropTablesSQL();
        foreach ($dropTables as $sql) {
            try {
                $connection->executeStatement($sql);
                $io->text("Dropped table (if existed): " . $this->extractTableName($sql));
            } catch (\Exception $e) {
                $io->warning("Could not drop table: " . $e->getMessage());
            }
        }

        // Create tables
        $createTables = $this->getCreateTablesSQL();
        foreach ($createTables as $sql) {
            try {
                $connection->executeStatement($sql);
                $io->success("Created table: " . $this->extractTableName($sql));
            } catch (\Exception $e) {
                $io->error("Could not create table: " . $e->getMessage());
            }
        }
    }
    
    private function extractTableName(string $sql): string
    {
        // Simple extraction of table name from SQL for display
        preg_match('/`([^`]+)`/', $sql, $matches);
        return $matches[1] ?? 'unknown';
    }
    
    private function getDropTablesSQL(): array
    {
        return [
            'DROP TABLE IF EXISTS `booking_table`',
            'DROP TABLE IF EXISTS `food_category`',
            'DROP TABLE IF EXISTS `menu_category`',
            'DROP TABLE IF EXISTS `booking`',
            'DROP TABLE IF EXISTS `restaurant_table`',
            'DROP TABLE IF EXISTS `food`',
            'DROP TABLE IF EXISTS `menu`',
            'DROP TABLE IF EXISTS `category`',
            'DROP TABLE IF EXISTS `gallery`',
            'DROP TABLE IF EXISTS `user`',
            'DROP TABLE IF EXISTS `restaurant`',
            'DROP TABLE IF EXISTS `hours`',
            'DROP TABLE IF EXISTS `allergen`',
        ];
    }
    
    private function getCreateTablesSQL(): array
    {
        return [
            // User table for authentication and user management
            "CREATE TABLE `user` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(180) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(64) DEFAULT NULL,
                last_name VARCHAR(64) DEFAULT NULL,
                roles JSON NOT NULL,
                default_guest_count INT DEFAULT NULL,
                allergies TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Restaurant information table
            "CREATE TABLE `restaurant` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(64) NOT NULL,
                description TEXT DEFAULT NULL,
                address VARCHAR(255) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                email VARCHAR(180) DEFAULT NULL,
                max_guests INT NOT NULL DEFAULT 50
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Gallery table for image gallery
            "CREATE TABLE `gallery` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(64) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                category VARCHAR(64) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                position INT DEFAULT 0,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Hours table for restaurant opening hours
            "CREATE TABLE `hours` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                day_of_week INT NOT NULL,
                lunch_opening_time TIME DEFAULT NULL,
                lunch_closing_time TIME DEFAULT NULL,
                dinner_opening_time TIME DEFAULT NULL,
                dinner_closing_time TIME DEFAULT NULL,
                is_closed TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Category table for food categories
            "CREATE TABLE `category` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(64) NOT NULL,
                description TEXT DEFAULT NULL,
                position INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Allergen table for food allergens
            "CREATE TABLE `allergen` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(64) NOT NULL,
                description TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Food table for menu items
            "CREATE TABLE `food` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                name VARCHAR(64) NOT NULL,
                description TEXT DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_seasonal TINYINT(1) DEFAULT NULL,
                popularity_score INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (category_id) REFERENCES category(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Menu table for restaurant menus
            "CREATE TABLE `menu` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(64) NOT NULL,
                description TEXT DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                start_date DATETIME DEFAULT NULL,
                end_date DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Restaurant table (for real tables in the restaurant)
            "CREATE TABLE `restaurant_table` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                number INT NOT NULL,
                seats INT NOT NULL,
                location VARCHAR(64) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Booking table for table reservations
            "CREATE TABLE `booking` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                date DATETIME NOT NULL,
                time_slot VARCHAR(10) NOT NULL,
                guest_count INT NOT NULL,
                last_name VARCHAR(64) NOT NULL,
                first_name VARCHAR(64) DEFAULT NULL,
                email VARCHAR(180) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL,
                notes TEXT DEFAULT NULL,
                allergies TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Menu_Category join table for menu-category relationships
            "CREATE TABLE `menu_category` (
                menu_id INT NOT NULL,
                category_id INT NOT NULL,
                PRIMARY KEY (menu_id, category_id),
                FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Food_Category join table for food-category relationships
            "CREATE TABLE `food_category` (
                food_id INT NOT NULL,
                category_id INT NOT NULL,
                PRIMARY KEY (food_id, category_id),
                FOREIGN KEY (food_id) REFERENCES food(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Booking_Table join table for booking-table relationships
            "CREATE TABLE `booking_table` (
                booking_id INT NOT NULL,
                table_id INT NOT NULL,
                PRIMARY KEY (booking_id, table_id),
                FOREIGN KEY (booking_id) REFERENCES booking(id) ON DELETE CASCADE,
                FOREIGN KEY (table_id) REFERENCES restaurant_table(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }
}
