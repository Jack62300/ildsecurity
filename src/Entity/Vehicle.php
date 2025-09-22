<?php
namespace App\Entity;

use App\Entity\FuelFillUp;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[Vich\Uploadable]
class Vehicle
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    #[Assert\NotBlank]
    private string $plate;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $model = null;

    // Nom de fichier stocké en base (pas le binaire)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoName = null;

    // Fichier virtuel pour Vich (non mappé en BDD)
    #[Vich\UploadableField(mapping: 'vehicle_photo', fileNameProperty: 'photoName')]
    private ?File $photoFile = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

     #[ORM\OneToMany(
        mappedBy: 'vehicle',
        targetEntity: FuelFillUp::class,
        orphanRemoval: true,
        cascade: ['persist','remove']
    )]
    private Collection $fillUps;

    public function __construct()
    {
        $this->fillUps = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPlate(): string { return $this->plate; }
    public function setPlate(string $p): self { $this->plate = $p; return $this; }

    public function getModel(): ?string { return $this->model; }
    public function setModel(?string $m): self { $this->model = $m; return $this; }

    public function getPhotoName(): ?string { return $this->photoName; }
    public function setPhotoName(?string $n): self { $this->photoName = $n; return $this; }

    public function setPhotoFile(?File $file = null): self
    {
        $this->photoFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }
    public function getPhotoFile(): ?File { return $this->photoFile; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $d): self { $this->updatedAt = $d; return $this; }

     /**
     * @return Collection<int, FuelFillUp>
     */
    public function getFillUps(): Collection
    {
        return $this->fillUps;
    }

    public function addFillUp(FuelFillUp $fillUp): self
    {
        if (!$this->fillUps->contains($fillUp)) {
            $this->fillUps->add($fillUp);
            $fillUp->setVehicle($this);
        }
        return $this;
    }

    public function removeFillUp(FuelFillUp $fillUp): self
    {
        if ($this->fillUps->removeElement($fillUp)) {
            if ($fillUp->getVehicle() === $this) {
                $fillUp->setVehicle(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return trim(($this->model ? $this->model.' ' : '').$this->plate) ?: $this->plate;
    }
}
