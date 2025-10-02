<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001232845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE modification_request (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, changes JSON NOT NULL COMMENT \'(DC2Type:json)\', submitted_by_name VARCHAR(180) DEFAULT NULL, submitted_by_email VARCHAR(180) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AF8314FA19EB6921 (client_id), INDEX IDX_AF8314FAFC6B21F1 (reviewed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE modification_request ADD CONSTRAINT FK_AF8314FA19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modification_request ADD CONSTRAINT FK_AF8314FAFC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE modification_request DROP FOREIGN KEY FK_AF8314FA19EB6921');
        $this->addSql('ALTER TABLE modification_request DROP FOREIGN KEY FK_AF8314FAFC6B21F1');
        $this->addSql('DROP TABLE modification_request');
    }
}
