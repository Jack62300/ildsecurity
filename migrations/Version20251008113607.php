<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008113607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_identifier VARCHAR(180) DEFAULT NULL, method VARCHAR(20) NOT NULL, route VARCHAR(255) DEFAULT NULL, path VARCHAR(1024) DEFAULT NULL, ip_hash VARCHAR(128) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, action VARCHAR(50) NOT NULL, object_type VARCHAR(255) DEFAULT NULL, object_id VARCHAR(255) DEFAULT NULL, payload JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX idx_audit_created_at (created_at), INDEX idx_audit_user_identifier (user_identifier), INDEX idx_audit_route (route), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE audit_log');
    }
}
