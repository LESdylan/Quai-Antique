<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503182743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE dietary_regime (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dish_dietary_regime (dish_id INT NOT NULL, dietary_regime_id INT NOT NULL, INDEX IDX_9FE2B856148EB0CB (dish_id), INDEX IDX_9FE2B856AAC8A98A (dietary_regime_id), PRIMARY KEY(dish_id, dietary_regime_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_allergen (user_id INT NOT NULL, allergen_id INT NOT NULL, INDEX IDX_C532ECCEA76ED395 (user_id), INDEX IDX_C532ECCE6E775A4A (allergen_id), PRIMARY KEY(user_id, allergen_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_dietary_regime (user_id INT NOT NULL, dietary_regime_id INT NOT NULL, INDEX IDX_C5953054A76ED395 (user_id), INDEX IDX_C5953054AAC8A98A (dietary_regime_id), PRIMARY KEY(user_id, dietary_regime_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dish_dietary_regime ADD CONSTRAINT FK_9FE2B856148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dish_dietary_regime ADD CONSTRAINT FK_9FE2B856AAC8A98A FOREIGN KEY (dietary_regime_id) REFERENCES dietary_regime (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_allergen ADD CONSTRAINT FK_C532ECCEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_allergen ADD CONSTRAINT FK_C532ECCE6E775A4A FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_dietary_regime ADD CONSTRAINT FK_C5953054A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_dietary_regime ADD CONSTRAINT FK_C5953054AAC8A98A FOREIGN KEY (dietary_regime_id) REFERENCES dietary_regime (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE allergen ADD icon VARCHAR(50) DEFAULT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE dish_dietary_regime DROP FOREIGN KEY FK_9FE2B856148EB0CB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dish_dietary_regime DROP FOREIGN KEY FK_9FE2B856AAC8A98A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_allergen DROP FOREIGN KEY FK_C532ECCEA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_allergen DROP FOREIGN KEY FK_C532ECCE6E775A4A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_dietary_regime DROP FOREIGN KEY FK_C5953054A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_dietary_regime DROP FOREIGN KEY FK_C5953054AAC8A98A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE dietary_regime
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE dish_dietary_regime
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_allergen
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_dietary_regime
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE allergen DROP icon, CHANGE name name VARCHAR(64) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL
        SQL);
    }
}
