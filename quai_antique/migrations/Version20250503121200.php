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
        return 'Migration created to match database state';
    }

    public function up(Schema $schema): void
    {
        // This is just a placeholder since this migration is already recorded in the database
        $this->addSql('-- This migration was already executed in the database');
    }

    public function down(Schema $schema): void
    {
        // This is just a placeholder for the down migration
        $this->addSql('-- Placeholder for down migration');
    }
}
