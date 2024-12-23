<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240425201313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hymn (hymn_id CHAR(50) NOT NULL, book_id VARCHAR(255) NOT NULL, number INT NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, tone VARCHAR(50) NOT NULL, INDEX IDX_FEAF0A9A16A2B381 (book_id), INDEX IDX_FEAF0A9A16A2B382 (number), PRIMARY KEY(hymn_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hymn ADD CONSTRAINT FK_FEAF0A9A16A2B381 FOREIGN KEY (book_id) REFERENCES book (book_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hymn DROP FOREIGN KEY FK_FEAF0A9A16A2B381');
        $this->addSql('DROP TABLE hymn');
    }
}
