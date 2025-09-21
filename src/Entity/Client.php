<?php

namespace App\Entity;

use App\Entity\ListAgence;
use App\Entity\ClientPhoto;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $information = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientPhoto::class, cascade: ['persist','remove'], orphanRemoval: true)]
    #[Assert\Count(max: 10, maxMessage: 'Maximum 10 photos')]
    private Collection $photos;

    #[ORM\ManyToOne(targetEntity: Organisme::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Organisme $organisme = null;   // remplace l'ancien string "gestionnaire"

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

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getCodetls(): ?string { return $this->codetls; }
    public function setCodetls(?string $codetls): static { $this->codetls = $codetls; return $this; }
    public function getKey(): ?string { return $this->key; }
    public function setKey(?string $key): static { $this->key = $key; return $this; }
    public function getCodeAlarme(): ?string { return $this->codeAlarme; }
    public function setCodeAlarme(?string $codeAlarme): static { $this->codeAlarme = $codeAlarme; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getKeycodeild(): ?string { return $this->keycodeild; }
    public function setKeycodeild(?string $keycodeild): static { $this->keycodeild = $keycodeild; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(string $adresse): static { $this->adresse = $adresse; return $this; }
    public function getInformation(): ?string { return $this->information; }
    public function setInformation(?string $information): static { $this->information = $information; return $this; }
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

    public function addPhoto(ClientPhoto $photo): static {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setClient($this);
        }
        return $this;
    }
    public function removePhoto(ClientPhoto $photo): static {
        if ($this->photos->removeElement($photo) && $photo->getClient() === $this) {
            $photo->setClient(null);
        }
        return $this;
    }
}