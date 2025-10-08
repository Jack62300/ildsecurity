<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007222307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trusted_device ADD device_id_hash VARCHAR(128) DEFAULT NULL, ADD last_seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP ip');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_device_hash ON trusted_device (user_id, device_id_hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_user_device_hash ON trusted_device');
        $this->addSql('ALTER TABLE trusted_device ADD ip VARCHAR(45) NOT NULL, DROP device_id_hash, DROP last_seen_at');
    }
}
