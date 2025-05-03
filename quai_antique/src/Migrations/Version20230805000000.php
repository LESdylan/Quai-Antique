<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230805000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dietary_regime field to user table';
    }

    public function up(Schema $schema): void
    {
        // Add the dietary_regime column to the user table
        $this->addSql('ALTER TABLE user ADD dietary_regime LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the dietary_regime column
        $this->addSql('ALTER TABLE user DROP dietary_regime');
    }
}
