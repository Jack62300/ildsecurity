<?php
// src/Entity/ListAgence.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'list_agence')]
class ListAgence
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 150)]
    private string $email = '';

    #[ORM\Column(name: 'code_agence', length: 50, unique: true)]
    private string $codeAgence = '';

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getCodeAgence(): string { return $this->codeAgence; }
    public function setCodeAgence(string $codeAgence): self { $this->codeAgence = $codeAgence; return $this; }

    public function __toString(): string
    {
        return $this->name ?: $this->codeAgence;
    }
}
