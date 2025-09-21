<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819231642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE list_agence (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, code_agence VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_FD1158871B47C20B (code_agence), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client ADD agence_id INT NOT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D725330D FOREIGN KEY (agence_id) REFERENCES list_agence (id)');
        $this->addSql('CREATE INDEX IDX_C7440455D725330D ON client (agence_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D725330D');
        $this->addSql('DROP TABLE list_agence');
        $this->addSql('DROP INDEX IDX_C7440455D725330D ON client');
        $this->addSql('ALTER TABLE client DROP agence_id');
    }
}
