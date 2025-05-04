<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503234804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE menu ADD image_id INT DEFAULT NULL, ADD image_filename VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu ADD CONSTRAINT FK_7D053A933DA5256D FOREIGN KEY (image_id) REFERENCES gallery (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7D053A933DA5256D ON menu (image_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE schedule ADD day_name VARCHAR(255) NOT NULL, ADD day_number SMALLINT NOT NULL, ADD is_closed TINYINT(1) NOT NULL, ADD lunch_opening_time TIME DEFAULT NULL, ADD lunch_closing_time TIME DEFAULT NULL, ADD dinner_opening_time TIME DEFAULT NULL, ADD dinner_closing_time TIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE menu DROP FOREIGN KEY FK_7D053A933DA5256D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_7D053A933DA5256D ON menu
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu DROP image_id, DROP image_filename
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE schedule DROP day_name, DROP day_number, DROP is_closed, DROP lunch_opening_time, DROP lunch_closing_time, DROP dinner_opening_time, DROP dinner_closing_time
        SQL);
    }
}
