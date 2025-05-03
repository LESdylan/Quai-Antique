#!/usr/bin/env php
<?php

// Fix migrations script to create properly formatted migration files

// Get project root directory
$rootDir = dirname(__DIR__);
$migrationsDir = $rootDir . '/migrations';

echo "Migration fix script starting...\n";

// Create fresh Version20231015000000.php migration file with proper syntax and content
$migrationContent = '<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Manual migration to create hours_exception table and add promotion columns
 */
final class Version20231015000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return \'Creates hours_exception table and adds columns to promotion table\';
    }

    public function up(Schema $schema): void
    {
        // Create hours_exception table
        $this->addSql(\'CREATE TABLE IF NOT EXISTS hours_exception (
            id INT AUTO_INCREMENT NOT NULL,
            date DATE NOT NULL,
            description VARCHAR(255) NOT NULL,
            is_closed TINYINT(1) NOT NULL,
            opening_time VARCHAR(5) DEFAULT NULL,
            closing_time VARCHAR(5) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB\');

        // Add columns to promotion table
        $this->addSql(\'ALTER TABLE promotion ADD COLUMN IF NOT EXISTS image_filename VARCHAR(255) NULL\');
        $this->addSql(\'ALTER TABLE promotion ADD COLUMN IF NOT EXISTS type VARCHAR(32) DEFAULT "banner" NOT NULL\');
        $this->addSql(\'ALTER TABLE promotion ADD COLUMN IF NOT EXISTS button_text VARCHAR(255) NULL\');
        $this->addSql(\'ALTER TABLE promotion ADD COLUMN IF NOT EXISTS button_link VARCHAR(255) NULL\');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(\'DROP TABLE IF EXISTS hours_exception\');
        
        // Remove columns from promotion table
        $this->addSql(\'ALTER TABLE promotion DROP COLUMN IF EXISTS button_link\');
        $this->addSql(\'ALTER TABLE promotion DROP COLUMN IF EXISTS button_text\');
        $this->addSql(\'ALTER TABLE promotion DROP COLUMN IF EXISTS type\');
        $this->addSql(\'ALTER TABLE promotion DROP COLUMN IF EXISTS image_filename\');
    }
}
';

// Create Version20250503121200.php migration file
$futureMigrationContent = '<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503121200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return \'Migration created to match database state\';
    }

    public function up(Schema $schema): void
    {
        // This is just a placeholder since this migration is already recorded in the database
        $this->addSql(\'-- This migration was already executed in the database\');
    }

    public function down(Schema $schema): void
    {
        // This is just a placeholder for the down migration
        $this->addSql(\'-- Placeholder for down migration\');
    }
}
';

echo "Creating migrations directory if it doesn't exist...\n";
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

echo "Writing first migration file...\n";
file_put_contents($migrationsDir . '/Version20231015000000.php', $migrationContent);

echo "Writing second migration file...\n";
file_put_contents($migrationsDir . '/Version20250503121200.php', $futureMigrationContent);

echo "Setting file permissions...\n";
chmod($migrationsDir . '/Version20231015000000.php', 0644);
chmod($migrationsDir . '/Version20250503121200.php', 0644);

echo "Clearing cache...\n";
// We'll create a system call to clear Symfony cache
passthru('cd ' . escapeshellarg($rootDir) . ' && php bin/console cache:clear');

echo "Fix completed.\n";
echo "Now try running your migrations again with:\n";
echo "php bin/console doctrine:migrations:sync-metadata-storage\n";
echo "php bin/console doctrine:migrations:migrate\n";
