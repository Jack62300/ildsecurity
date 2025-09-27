<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // En-tête
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ville = null;

    // VEILLE : une case par ligne => booléens
    #[ORM\Column(type: 'boolean')] private bool $vIntrusion = false;
    #[ORM\Column(type: 'boolean')] private bool $vIncendie = false;
    #[ORM\Column(type: 'boolean')] private bool $vAgression = false;
    #[ORM\Column(type: 'boolean')] private bool $vDefautSecteur = false;

    #[ORM\Column(type: 'boolean')] private bool $vDefautBatterie = false;
    #[ORM\Column(type: 'boolean')] private bool $vAbsTest = false;
    #[ORM\Column(type: 'boolean')] private bool $vAbsMes = false;
    #[ORM\Column(type: 'boolean')] private bool $vMhsNonAutorisee = false;

    #[ORM\Column(type: 'boolean')] private bool $vMaintenance = false;
    #[ORM\Column(type: 'boolean')] private bool $vTechnique = false;
    #[ORM\Column(type: 'boolean')] private bool $vAscenseur = false;
    #[ORM\Column(type: 'boolean')] private bool $vAutre = false;

    // COMPTE RENDU (radio)
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $compteRendu = null; // intervention|ronde|gardiennage

    // Moyen d’accès (oui/non)
    #[ORM\Column(type: 'boolean')]
    private bool $avecMoyenAcces = false;

    // Date du bon
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateBon = null;

    // Heures
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heureAppel = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heureDepart = null;

    // ------- DÉTAIL DE LA PRESTATION (1 ligne = 1 choix) -------

    // Constat météo : vent_fort|pluie|orage|brouillard|neige|null
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $constatMeteo = null;

    // Circulation : bonne|mauvaise|null + motif si mauvaise
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $circulation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $circulationMotif = null;

    // Circuit de vérification : interieur|exterieur|null + précisions si extérieur
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $circuitVerification = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $circuitPoints = null;

    // Lumière allumée : non|oui + pièce si oui
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $lumiereAllumee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lumierePiece = null;

    // Issue(s) ouverte(s) : non|oui + lesquelles si oui
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $issuesOuvertes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $issuesLesquelles = null;

    // Sirène en fonction : non|oui
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $sireneEnFonction = null;

    // Système : en_service|hors_service|null
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $systemeEtat = null;

    // Remise en service du système : non|oui
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $remiseEnService = null;

    // Zone(s) : anomalies|zones_isolees|null
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $zones = null;

    // Effraction constatée : non|oui|null
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $effraction = null;

    // Présence : client|police|gendarmerie|pompiers|null
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $presence = [];

    // Mise en place de : ads|maitre_chien|null + demandé par
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $miseEnPlace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $demandePar = null;

    // Personnel sur place : non|oui + note si oui
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $personnelSurPlace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personnelNote = null;

    // Véhicule sur place : non|oui + marque + numero
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $vehiculeSurPlace = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $vehiculeMarque = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $vehiculeNumero = null;

    // Présence d’animaux : non|oui + espèce
    #[ORM\Column(length: 3, nullable: true)]
    private ?string $animaux = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $animauxEspece = null;

    // Commentaires
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaires = null;

    // Bon d'intervention
    #[ORM\Column(length: 6, unique: true, nullable: true)]
    private ?string $bonNumero = null;

    // Dépôt du bon : boite_lettres|bureau|autre|null + précision
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bonDepose = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bonDeposePrecision = null;

    // Pied de page
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intervenant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entreprise = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signaturePath = null;



    // -------- Getters / Setters --------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }
    public function setClient(?string $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }
    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }
    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function isVIntrusion(): bool
    {
        return $this->vIntrusion;
    }
    public function setVIntrusion(bool $vIntrusion): self
    {
        $this->vIntrusion = $vIntrusion;
        return $this;
    }

    public function isVIncendie(): bool
    {
        return $this->vIncendie;
    }
    public function setVIncendie(bool $vIncendie): self
    {
        $this->vIncendie = $vIncendie;
        return $this;
    }

    public function isVAgression(): bool
    {
        return $this->vAgression;
    }
    public function setVAgression(bool $vAgression): self
    {
        $this->vAgression = $vAgression;
        return $this;
    }

    public function isVDefautSecteur(): bool
    {
        return $this->vDefautSecteur;
    }
    public function setVDefautSecteur(bool $vDefautSecteur): self
    {
        $this->vDefautSecteur = $vDefautSecteur;
        return $this;
    }

    public function isVDefautBatterie(): bool
    {
        return $this->vDefautBatterie;
    }
    public function setVDefautBatterie(bool $vDefautBatterie): self
    {
        $this->vDefautBatterie = $vDefautBatterie;
        return $this;
    }

    public function isVAbsTest(): bool
    {
        return $this->vAbsTest;
    }
    public function setVAbsTest(bool $vAbsTest): self
    {
        $this->vAbsTest = $vAbsTest;
        return $this;
    }

    public function isVAbsMes(): bool
    {
        return $this->vAbsMes;
    }
    public function setVAbsMes(bool $vAbsMes): self
    {
        $this->vAbsMes = $vAbsMes;
        return $this;
    }

    public function isVMhsNonAutorisee(): bool
    {
        return $this->vMhsNonAutorisee;
    }
    public function setVMhsNonAutorisee(bool $vMhsNonAutorisee): self
    {
        $this->vMhsNonAutorisee = $vMhsNonAutorisee;
        return $this;
    }

    public function isVMaintenance(): bool
    {
        return $this->vMaintenance;
    }
    public function setVMaintenance(bool $vMaintenance): self
    {
        $this->vMaintenance = $vMaintenance;
        return $this;
    }

    public function isVTechnique(): bool
    {
        return $this->vTechnique;
    }
    public function setVTechnique(bool $vTechnique): self
    {
        $this->vTechnique = $vTechnique;
        return $this;
    }

    public function isVAscenseur(): bool
    {
        return $this->vAscenseur;
    }
    public function setVAscenseur(bool $vAscenseur): self
    {
        $this->vAscenseur = $vAscenseur;
        return $this;
    }

    public function isVAutre(): bool
    {
        return $this->vAutre;
    }
    public function setVAutre(bool $vAutre): self
    {
        $this->vAutre = $vAutre;
        return $this;
    }

    public function getCompteRendu(): ?string
    {
        return $this->compteRendu;
    }
    public function setCompteRendu(?string $compteRendu): self
    {
        $this->compteRendu = $compteRendu;
        return $this;
    }

    public function isAvecMoyenAcces(): bool
    {
        return $this->avecMoyenAcces;
    }
    public function setAvecMoyenAcces(bool $avecMoyenAcces): self
    {
        $this->avecMoyenAcces = $avecMoyenAcces;
        return $this;
    }

    public function getDateBon(): ?\DateTimeInterface
    {
        return $this->dateBon;
    }
    public function setDateBon(?\DateTimeInterface $dateBon): self
    {
        $this->dateBon = $dateBon;
        return $this;
    }

    public function getHeureAppel(): ?\DateTimeInterface
    {
        return $this->heureAppel;
    }
    public function setHeureAppel(?\DateTimeInterface $heureAppel): self
    {
        $this->heureAppel = $heureAppel;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }
    public function setHeureArrivee(?\DateTimeInterface $heureArrivee): self
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }
    public function setHeureDepart(?\DateTimeInterface $heureDepart): self
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getConstatMeteo(): ?string
    {
        return $this->constatMeteo;
    }
    public function setConstatMeteo(?string $constatMeteo): self
    {
        $this->constatMeteo = $constatMeteo;
        return $this;
    }

    public function getCirculation(): ?string
    {
        return $this->circulation;
    }
    public function setCirculation(?string $circulation): self
    {
        $this->circulation = $circulation;
        return $this;
    }

    public function getCirculationMotif(): ?string
    {
        return $this->circulationMotif;
    }
    public function setCirculationMotif(?string $circulationMotif): self
    {
        $this->circulationMotif = $circulationMotif;
        return $this;
    }

    public function getCircuitVerification(): array
    {
        return $this->circuitVerification ?? [];
    }

    public function setCircuitVerification(array $circuitVerification): self
    {
        // on normalise les valeurs (supprime doublons, réindexe)
        $this->circuitVerification = array_values(array_unique($circuitVerification));
        return $this;
    }

    public function getCircuitPoints(): ?string
    {
        return $this->circuitPoints;
    }
    public function setCircuitPoints(?string $circuitPoints): self
    {
        $this->circuitPoints = $circuitPoints;
        return $this;
    }

    public function getLumiereAllumee(): ?string
    {
        return $this->lumiereAllumee;
    }
    public function setLumiereAllumee(?string $lumiereAllumee): self
    {
        $this->lumiereAllumee = $lumiereAllumee;
        return $this;
    }

    public function getLumierePiece(): ?string
    {
        return $this->lumierePiece;
    }
    public function setLumierePiece(?string $lumierePiece): self
    {
        $this->lumierePiece = $lumierePiece;
        return $this;
    }

    public function getIssuesOuvertes(): ?string
    {
        return $this->issuesOuvertes;
    }
    public function setIssuesOuvertes(?string $issuesOuvertes): self
    {
        $this->issuesOuvertes = $issuesOuvertes;
        return $this;
    }

    public function getIssuesLesquelles(): ?string
    {
        return $this->issuesLesquelles;
    }
    public function setIssuesLesquelles(?string $issuesLesquelles): self
    {
        $this->issuesLesquelles = $issuesLesquelles;
        return $this;
    }

    public function getSireneEnFonction(): ?string
    {
        return $this->sireneEnFonction;
    }
    public function setSireneEnFonction(?string $sireneEnFonction): self
    {
        $this->sireneEnFonction = $sireneEnFonction;
        return $this;
    }

    public function getSystemeEtat(): ?string
    {
        return $this->systemeEtat;
    }
    public function setSystemeEtat(?string $systemeEtat): self
    {
        $this->systemeEtat = $systemeEtat;
        return $this;
    }

    public function getRemiseEnService(): ?string
    {
        return $this->remiseEnService;
    }
    public function setRemiseEnService(?string $remiseEnService): self
    {
        $this->remiseEnService = $remiseEnService;
        return $this;
    }

    public function getZones(): ?string
    {
        return $this->zones;
    }
    public function setZones(?string $zones): self
    {
        $this->zones = $zones;
        return $this;
    }

    public function getEffraction(): ?string
    {
        return $this->effraction;
    }
    public function setEffraction(?string $effraction): self
    {
        $this->effraction = $effraction;
        return $this;
    }

    public function getPresence(): array
    {
        return $this->presence ?? [];
    }

    public function setPresence(array $presence): self
    {
        $this->presence = array_values(array_unique($presence));
        return $this;
    }

    public function getMiseEnPlace(): ?string
    {
        return $this->miseEnPlace;
    }
    public function setMiseEnPlace(?string $miseEnPlace): self
    {
        $this->miseEnPlace = $miseEnPlace;
        return $this;
    }

    public function getDemandePar(): ?string
    {
        return $this->demandePar;
    }
    public function setDemandePar(?string $demandePar): self
    {
        $this->demandePar = $demandePar;
        return $this;
    }

    public function getPersonnelSurPlace(): ?string
    {
        return $this->personnelSurPlace;
    }
    public function setPersonnelSurPlace(?string $personnelSurPlace): self
    {
        $this->personnelSurPlace = $personnelSurPlace;
        return $this;
    }

    public function getPersonnelNote(): ?string
    {
        return $this->personnelNote;
    }
    public function setPersonnelNote(?string $personnelNote): self
    {
        $this->personnelNote = $personnelNote;
        return $this;
    }

    public function getVehiculeSurPlace(): ?string
    {
        return $this->vehiculeSurPlace;
    }
    public function setVehiculeSurPlace(?string $vehiculeSurPlace): self
    {
        $this->vehiculeSurPlace = $vehiculeSurPlace;
        return $this;
    }

    public function getVehiculeMarque(): ?string
    {
        return $this->vehiculeMarque;
    }
    public function setVehiculeMarque(?string $vehiculeMarque): self
    {
        $this->vehiculeMarque = $vehiculeMarque;
        return $this;
    }

    public function getVehiculeNumero(): ?string
    {
        return $this->vehiculeNumero;
    }
    public function setVehiculeNumero(?string $vehiculeNumero): self
    {
        $this->vehiculeNumero = $vehiculeNumero;
        return $this;
    }

    public function getAnimaux(): ?string
    {
        return $this->animaux;
    }
    public function setAnimaux(?string $animaux): self
    {
        $this->animaux = $animaux;
        return $this;
    }

    public function getAnimauxEspece(): ?string
    {
        return $this->animauxEspece;
    }
    public function setAnimauxEspece(?string $animauxEspece): self
    {
        $this->animauxEspece = $animauxEspece;
        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }
    public function setCommentaires(?string $commentaires): self
    {
        $this->commentaires = $commentaires;
        return $this;
    }

    public function getBonNumero(): ?string
    {
        return $this->bonNumero;
    }
    public function setBonNumero(?string $bonNumero): self
    {
        $this->bonNumero = $bonNumero;
        return $this;
    }

    public function getBonDepose(): ?string
    {
        return $this->bonDepose;
    }
    public function setBonDepose(?string $bonDepose): self
    {
        $this->bonDepose = $bonDepose;
        return $this;
    }

    public function getBonDeposePrecision(): ?string
    {
        return $this->bonDeposePrecision;
    }
    public function setBonDeposePrecision(?string $bonDeposePrecision): self
    {
        $this->bonDeposePrecision = $bonDeposePrecision;
        return $this;
    }

    public function getIntervenant(): ?string
    {
        return $this->intervenant;
    }
    public function setIntervenant(?string $intervenant): self
    {
        $this->intervenant = $intervenant;
        return $this;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }
    public function setEntreprise(?string $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function getSignaturePath(): ?string
    {
        return $this->signaturePath;
    }
    public function setSignaturePath(?string $signaturePath): self
    {
        $this->signaturePath = $signaturePath;
        return $this;
    }
}
