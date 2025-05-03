<?php

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
        return 'Creates hours_exception table and adds columns to promotion table';
    }

    public function up(Schema $schema): void
    {
        // Create hours_exception table
        $this->addSql('CREATE TABLE IF NOT EXISTS hours_exception (
            id INT AUTO_INCREMENT NOT NULL,
            date DATE NOT NULL,
            description VARCHAR(255) NOT NULL,
            is_closed TINYINT(1) NOT NULL,
            opening_time VARCHAR(5) DEFAULT NULL,
            closing_time VARCHAR(5) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add columns to promotion table
        $this->addSql('ALTER TABLE promotion ADD COLUMN IF NOT EXISTS image_filename VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE promotion ADD COLUMN IF NOT EXISTS type VARCHAR(32) DEFAULT "banner" NOT NULL');
        $this->addSql('ALTER TABLE promotion ADD COLUMN IF NOT EXISTS button_text VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE promotion ADD COLUMN IF NOT EXISTS button_link VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS hours_exception');
        
        // Remove columns from promotion table
        $this->addSql('ALTER TABLE promotion DROP COLUMN IF EXISTS button_link');
        $this->addSql('ALTER TABLE promotion DROP COLUMN IF EXISTS button_text');
        $this->addSql('ALTER TABLE promotion DROP COLUMN IF EXISTS type');
        $this->addSql('ALTER TABLE promotion DROP COLUMN IF EXISTS image_filename');
    }
}
