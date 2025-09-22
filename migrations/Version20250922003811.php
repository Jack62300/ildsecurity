<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922003811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fuel_fillup (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, filled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', odometer INT NOT NULL, price_per_litre NUMERIC(8, 3) NOT NULL, liters NUMERIC(8, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, distance_km INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FF543401545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fuel_fillup ADD CONSTRAINT FK_FF543401545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fuel_fill_up DROP FOREIGN KEY FK_BD52CD60545317D1');
        $this->addSql('DROP TABLE fuel_fill_up');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455D725330D FOREIGN KEY (agence_id) REFERENCES list_agence (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fuel_fill_up (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, filled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', odometer_km INT NOT NULL, km_since_last INT DEFAULT 0 NOT NULL, liters DOUBLE PRECISION NOT NULL, price_per_liter DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, INDEX IDX_BD52CD60545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE fuel_fill_up ADD CONSTRAINT FK_BD52CD60545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE fuel_fillup DROP FOREIGN KEY FK_FF543401545317D1');
        $this->addSql('DROP TABLE fuel_fillup');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455D725330D');
    }
}
