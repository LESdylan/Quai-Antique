<?php

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
        return 'Migration created to fix inconsistency in the migration system';
    }

    public function up(Schema $schema): void
    {
        // This migration existed in the database but not in the code
        // Add any SQL that was likely part of this migration
        // For example: $this->addSql('ALTER TABLE promotion ADD COLUMN image_filename VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        // This is the reverse of what the up() method does
        // For example: $this->addSql('ALTER TABLE promotion DROP COLUMN image_filename');
    }
}
