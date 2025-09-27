<?php

namespace App\Entity;

use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
#[ORM\Table(name: 'phone_number')]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom d'affichage (personne/service)
    #[ORM\Column(length: 150)]
    private string $name = '';

    // Le numéro (garde string: +, espaces, etc.)
    #[ORM\Column(length: 40)]
    private string $number = '';

    #[ORM\ManyToOne(inversedBy: 'numbers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?PhoneCategory $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): self { $this->number = $number; return $this; }

    public function getCategory(): ?PhoneCategory { return $this->category; }
    public function setCategory(?PhoneCategory $category): self { $this->category = $category; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes; return $this; }
}
