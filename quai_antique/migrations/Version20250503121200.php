<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to fix image_filename column in promotion table
 */
final class Version20250503121200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds image_filename column to promotion table';
    }

    public function up(Schema $schema): void
    {
        // This is a placeholder since this migration was already executed in the database
        $this->addSql('-- Migration previously executed manually');
    }

    public function down(Schema $schema): void
    {
        // Down migration is empty since this is just a placeholder
    }
}
