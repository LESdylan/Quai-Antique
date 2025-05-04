<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503222322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, day_of_week INT NOT NULL, meal_type VARCHAR(20) NOT NULL, opening_time TIME NOT NULL, closing_time TIME NOT NULL, is_active TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE setting (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, `group` VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_9F74B8985E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE gallery ADD category VARCHAR(50) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', DROP is_active, CHANGE title title VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD replied_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE email email VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE schedule
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE setting
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE gallery ADD is_active TINYINT(1) NOT NULL, DROP category, DROP updated_at, CHANGE title title VARCHAR(64) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP replied_at, CHANGE email email VARCHAR(180) NOT NULL
        SQL);
    }
}
