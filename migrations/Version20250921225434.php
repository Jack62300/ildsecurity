<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921225434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS app_extension (code VARCHAR(64) NOT NULL, enabled TINYINT(1) NOT NULL, label VARCHAR(128) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS fuel_fill_up (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, filled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', odometer_km INT NOT NULL, km_since_last INT DEFAULT 0 NOT NULL, liters DOUBLE PRECISION NOT NULL, price_per_liter DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION NOT NULL, INDEX IDX_BD52CD60545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS vehicle (id INT AUTO_INCREMENT NOT NULL, plate VARCHAR(32) NOT NULL, model VARCHAR(64) DEFAULT NULL, photo_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fuel_fill_up ADD CONSTRAINT FK_BD52CD60545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fuel_fill_up DROP FOREIGN KEY FK_BD52CD60545317D1');
        $this->addSql('DROP TABLE app_extension');
        $this->addSql('DROP TABLE fuel_fill_up');
        $this->addSql('DROP TABLE vehicle');
    }
}
