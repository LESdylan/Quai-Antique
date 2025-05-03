<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230801000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table_type and table_number columns to reservation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD table_type VARCHAR(20) DEFAULT NULL, ADD table_number INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP table_type, DROP table_number');
    }
}
