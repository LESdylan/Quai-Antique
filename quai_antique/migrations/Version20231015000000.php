<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231015000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database schema';
    }

    public function up(Schema $schema): void
    {
        // Simply create an empty migration to satisfy Symfony's service container
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        // Do nothing for the down migration
    }
}
