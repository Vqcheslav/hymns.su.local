<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240425202829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE verse (verse_id INT AUTO_INCREMENT NOT NULL, hymn_id CHAR(50) NOT NULL, position SMALLINT NOT NULL, is_chorus TINYINT(1) NOT NULL, lyrics VARCHAR(500) NOT NULL, chords VARCHAR(255) NOT NULL, INDEX IDX_D2F7E69F2A43AE1B (hymn_id), PRIMARY KEY(verse_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE verse ADD CONSTRAINT FK_D2F7E69F2A43AE1B FOREIGN KEY (hymn_id) REFERENCES hymn (hymn_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verse DROP FOREIGN KEY FK_D2F7E69F2A43AE1B');
        $this->addSql('DROP TABLE verse');
    }
}
