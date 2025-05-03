<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230802000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add capacity-related fields to restaurant table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant ADD max_capacity INT DEFAULT 50');
        $this->addSql('ALTER TABLE restaurant ADD max_tables_small INT DEFAULT 6');
        $this->addSql('ALTER TABLE restaurant ADD max_tables_medium INT DEFAULT 8');
        $this->addSql('ALTER TABLE restaurant ADD max_tables_large INT DEFAULT 4');
        $this->addSql('ALTER TABLE restaurant ADD time_slot_duration INT DEFAULT 30');
        $this->addSql('ALTER TABLE restaurant ADD max_reservations_per_slot INT DEFAULT 10');
        $this->addSql('ALTER TABLE restaurant ADD buffer_between_slots INT DEFAULT 15');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE restaurant DROP max_capacity');
        $this->addSql('ALTER TABLE restaurant DROP max_tables_small');
        $this->addSql('ALTER TABLE restaurant DROP max_tables_medium');
        $this->addSql('ALTER TABLE restaurant DROP max_tables_large');
        $this->addSql('ALTER TABLE restaurant DROP time_slot_duration');
        $this->addSql('ALTER TABLE restaurant DROP max_reservations_per_slot');
        $this->addSql('ALTER TABLE restaurant DROP buffer_between_slots');
    }
}
