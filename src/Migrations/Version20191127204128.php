<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191127204128 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE position (position_id INT UNSIGNED AUTO_INCREMENT NOT NULL, position VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(position_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE order_block ADD first_date DATETIME NOT NULL, ADD max_profit INT UNSIGNED DEFAULT NULL, ADD max_lose INT UNSIGNED DEFAULT NULL, ADD is_vasya_signal TINYINT(1) DEFAULT NULL, ADD is_vasya_trend TINYINT(1) DEFAULT NULL, ADD is_learning TINYINT(1) DEFAULT NULL, ADD is_emotional TINYINT(1) DEFAULT NULL, ADD position_id INT DEFAULT NULL');
        $this->addSql("INSERT INTO `position` (`position_id`, `position`) VALUES (1, 'Терминал'), (2, 'Постель'), (3, 'Работа'), (4, 'Дорога'), (5, 'Другое')");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE position');
        $this->addSql('ALTER TABLE order_block DROP first_date, DROP max_profit, DROP max_lose, DROP is_vasya_signal, DROP is_vasya_trend, DROP is_learning, DROP is_emotional, DROP position_id');
    }
}
