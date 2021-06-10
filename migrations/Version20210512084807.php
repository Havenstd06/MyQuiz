<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210512084807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `score` (`id` INT NOT NULL AUTO_INCREMENT,`user_id` INT unsigned, `categorie_id` INT unsigned, `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci,`score` INT, `date` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci, PRIMARY KEY (`id`))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `score`');
    }
}
