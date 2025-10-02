<?php

namespace App\Entity;

use App\Entity\ListAgence;
use App\Entity\ClientPhoto;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(
    name: 'client',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_client_adresse_normalized', columns: ['adresse_normalized'])
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['adresseNormalized'],
    message: 'Un client avec cette adresse existe déjà.'
)]
class Client
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codetls = null;

    #[ORM\Column(name: 'client_key', type: Types::TEXT, nullable: true)]
    private ?string $key = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codeAlarme = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $keycodeild = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $adresse = null;

    /**
     * Colonne utilisée pour l’unicité (adresse normalisée).
     * Laisse nullable pour les anciens enregistrements, mais elle sera renseignée automatiquement.
     */
    #[ORM\Column(name: 'adresse_normalized', length: 255, nullable: true)]
    private ?string $adresseNormalized = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $information = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientPhoto::class, cascade: ['persist','remove'], orphanRemoval: true)]
    #[Assert\Count(max: 10, maxMessage: 'Maximum 10 photos')]
    private Collection $photos;

    #[ORM\ManyToOne(targetEntity: Organisme::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Organisme $organisme = null;

    #[ORM\ManyToOne(targetEntity: ListAgence::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ListAgence $agence = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    // ---------- Getters / Setters de base ----------

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getCodetls(): ?string { return $this->codetls; }
    public function setCodetls(?string $codetls): self { $this->codetls = $codetls; return $this; }

    public function getKey(): ?string { return $this->key; }
    public function setKey(?string $key): self { $this->key = $key; return $this; }

    public function getCodeAlarme(): ?string { return $this->codeAlarme; }
    public function setCodeAlarme(?string $codeAlarme): self { $this->codeAlarme = $codeAlarme; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getKeycodeild(): ?string { return $this->keycodeild; }
    public function setKeycodeild(?string $keycodeild): self { $this->keycodeild = $keycodeild; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        // Met à jour la forme normalisée immédiatement
        $this->adresseNormalized = self::normalizeAddress($adresse);
        return $this;
    }

    public function getAdresseNormalized(): ?string { return $this->adresseNormalized; }
    /** Privé pour éviter la modification directe hors normalisation */
    private function setAdresseNormalized(?string $v): void { $this->adresseNormalized = $v; }

    public function getInformation(): ?string { return $this->information; }
    public function setInformation(?string $information): self { $this->information = $information; return $this; }

    public function getOrganisme(): ?Organisme { return $this->organisme; }
    public function setOrganisme(?Organisme $organisme): self { $this->organisme = $organisme; return $this; }

    public function getAgence(): ?ListAgence { return $this->agence; }
    public function setAgence(?ListAgence $agence): self { $this->agence = $agence; return $this; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $lat): self { $this->latitude = $lat; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $lng): self { $this->longitude = $lng; return $this; }

    /** @return Collection<int, ClientPhoto> */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(ClientPhoto $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setClient($this);
        }
        return $this;
    }

    public function removePhoto(ClientPhoto $photo): self
    {
        if ($this->photos->removeElement($photo) && $photo->getClient() === $this) {
            $photo->setClient(null);
        }
        return $this;
    }

    // ---------- Lifecycle: sécurise la synchro avant insert/update ----------

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function syncAdresseNormalized(): void
    {
        $this->adresseNormalized = self::normalizeAddress((string) $this->adresse);
    }

    // ---------- Utilitaires ----------

    /**
     * Normalisation de l’adresse pour la déduplication :
     * - trim, minuscules
     * - suppression des accents
     * - remplace ponctuation par espace
     * - compaction des espaces
     * - tronque à 255 caractères (longueur colonne)
     */
    public static function normalizeAddress(?string $raw): ?string
    {
        if (!$raw) return null;

        $s = trim($raw);
        $s = mb_strtolower($s, 'UTF-8');

        if (class_exists(\Transliterator::class)) {
            $tr = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($tr) {
                $s = $tr->transliterate($s);
            }
        }

        $s = preg_replace('/[^a-z0-9\s]/u', ' ', $s); // garde lettres/chiffres/espaces
        $s = preg_replace('/\s+/u', ' ', $s);         // compresse les espaces
        $s = trim($s);

        return mb_substr($s, 0, 255, 'UTF-8');
    }
}
