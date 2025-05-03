<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230807000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dietary_regime column to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD dietary_regime LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP dietary_regime');
    }
}
