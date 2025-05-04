<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503214246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD status VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE read_at read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE message content LONGTEXT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP status, CHANGE email email VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE read_at read_at DATETIME DEFAULT NULL, CHANGE content message LONGTEXT NOT NULL
        SQL);
    }
}
