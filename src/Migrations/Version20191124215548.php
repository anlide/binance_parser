<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191124215548 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `order` (order_id INT UNSIGNED AUTO_INCREMENT NOT NULL, pair_id INT UNSIGNED NOT NULL, result INT DEFAULT 0 NOT NULL, INDEX pair_id (pair_id), PRIMARY KEY(order_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB ');
        $this->addSql('CREATE TABLE order_action (order_id INT UNSIGNED NOT NULL, `index` INT UNSIGNED NOT NULL, action_buy TINYINT(1) NOT NULL, quantity DOUBLE PRECISION UNSIGNED NOT NULL, price INT UNSIGNED NOT NULL, amount INT UNSIGNED NOT NULL, `time` DATETIME DEFAULT NULL, INDEX order_id (order_id), PRIMARY KEY(order_id, `index`)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB ');
        $this->addSql('CREATE TABLE pair (pair_id INT UNSIGNED AUTO_INCREMENT NOT NULL, name INT NOT NULL, PRIMARY KEY(pair_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_action');
        $this->addSql('DROP TABLE pair');
    }
}
