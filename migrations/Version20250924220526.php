<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924220526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, client VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, ville VARCHAR(120) DEFAULT NULL, v_intrusion TINYINT(1) NOT NULL, v_incendie TINYINT(1) NOT NULL, v_agression TINYINT(1) NOT NULL, v_defaut_secteur TINYINT(1) NOT NULL, v_defaut_batterie TINYINT(1) NOT NULL, v_abs_test TINYINT(1) NOT NULL, v_abs_mes TINYINT(1) NOT NULL, v_mhs_non_autorisee TINYINT(1) NOT NULL, v_maintenance TINYINT(1) NOT NULL, v_technique TINYINT(1) NOT NULL, v_ascenseur TINYINT(1) NOT NULL, v_autre TINYINT(1) NOT NULL, compte_rendu VARCHAR(20) DEFAULT NULL, avec_moyen_acces TINYINT(1) NOT NULL, date_bon DATE DEFAULT NULL, heure_appel DATETIME DEFAULT NULL, heure_arrivee DATETIME DEFAULT NULL, heure_depart DATETIME DEFAULT NULL, constat_meteo VARCHAR(20) DEFAULT NULL, circulation VARCHAR(10) DEFAULT NULL, circulation_motif VARCHAR(255) DEFAULT NULL, circuit_verification VARCHAR(15) DEFAULT NULL, circuit_points VARCHAR(255) DEFAULT NULL, lumiere_allumee VARCHAR(3) DEFAULT NULL, lumiere_piece VARCHAR(255) DEFAULT NULL, issues_ouvertes VARCHAR(3) DEFAULT NULL, issues_lesquelles VARCHAR(255) DEFAULT NULL, sirene_en_fonction VARCHAR(3) DEFAULT NULL, systeme_etat VARCHAR(20) DEFAULT NULL, remise_en_service VARCHAR(3) DEFAULT NULL, zones VARCHAR(20) DEFAULT NULL, effraction VARCHAR(3) DEFAULT NULL, presence VARCHAR(20) DEFAULT NULL, mise_en_place VARCHAR(20) DEFAULT NULL, demande_par VARCHAR(255) DEFAULT NULL, personnel_sur_place VARCHAR(3) DEFAULT NULL, personnel_note VARCHAR(255) DEFAULT NULL, vehicule_sur_place VARCHAR(3) DEFAULT NULL, vehicule_marque VARCHAR(120) DEFAULT NULL, vehicule_numero VARCHAR(120) DEFAULT NULL, animaux VARCHAR(3) DEFAULT NULL, animaux_espece VARCHAR(120) DEFAULT NULL, commentaires LONGTEXT DEFAULT NULL, bon_numero VARCHAR(50) DEFAULT NULL, bon_depose VARCHAR(20) DEFAULT NULL, bon_depose_precision VARCHAR(255) DEFAULT NULL, intervenant VARCHAR(255) DEFAULT NULL, entreprise VARCHAR(255) DEFAULT NULL, signature VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE intervention');
    }
}
