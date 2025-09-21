<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902155707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD organisme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404555DDD38F5 FOREIGN KEY (organisme_id) REFERENCES organisme (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C74404555DDD38F5 ON client (organisme_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404555DDD38F5');
        $this->addSql('DROP INDEX IDX_C74404555DDD38F5 ON client');
        $this->addSql('ALTER TABLE client DROP organisme_id');
    }
}
