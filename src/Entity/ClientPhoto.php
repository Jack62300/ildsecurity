<?php

namespace App\Entity;

use App\Security\Auditable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientPhotoRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientPhotoRepository::class)]
#[Vich\Uploadable]
#[Auditable]
class ClientPhoto
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // nom du fichier stocké
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    // champ d'upload (non persisté)
    #[Vich\UploadableField(mapping: 'client_photos', fileNameProperty: 'imageName')]
    #[Assert\Image(maxSize: '10M')]
    private ?File $imageFile = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Client $client = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): static { $this->imageName = $imageName; return $this; }

    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $file = null): static {
        $this->imageFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
