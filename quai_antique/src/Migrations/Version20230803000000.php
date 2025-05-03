<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230803000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename user_allergen and user_dietary_regime tables to simpler names';
    }

    public function up(Schema $schema): void
    {
        // Rename tables
        $this->addSql('RENAME TABLE user_allergen TO allergen');
        $this->addSql('RENAME TABLE user_dietary_regime TO dietary_regime');
    }

    public function down(Schema $schema): void
    {
        // Revert changes
        $this->addSql('RENAME TABLE allergen TO user_allergen');
        $this->addSql('RENAME TABLE dietary_regime TO user_dietary_regime');
    }
}
