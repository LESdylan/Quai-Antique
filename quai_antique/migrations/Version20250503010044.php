<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250503010044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Custom migration generated for AddDescriptionToImage';
    }

    public function up(Schema $schema): void
    {
        // This migration was generated with a custom tool
        // because doctrine:migrations:diff has issues with MySQL/MariaDB
        $this->addSql('ALTER TABLE `image` ADD COLUMN `description` TEXT NULL');
    }

    public function down(Schema $schema): void
    {
        // This migration was generated with a custom tool
        $this->addSql('ALTER TABLE `image` DROP COLUMN `description`');
    }
}