<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924221619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intervention CHANGE bon_numero bon_numero VARCHAR(6) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D11814ABE12699A4 ON intervention (bon_numero)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D11814ABE12699A4 ON intervention');
        $this->addSql('ALTER TABLE intervention CHANGE bon_numero bon_numero VARCHAR(50) DEFAULT NULL');
    }
}
