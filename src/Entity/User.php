<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet e-mail est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Rôles disponibles (utilisés dans le FormType)
    public const ROLE_ADMIN     = 'ROLE_ADMIN';
    public const ROLE_DEV       = 'ROLE_DEV';
    public const ROLE_SUPPORT   = 'ROLE_SUPPORT';
    public const ROLE_OPERATEUR = 'ROLE_OPERATEUR';
    public const ROLE_USER      = 'ROLE_USER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private string $password = '';

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $agence = null;

    public function getId(): ?int { return $this->id; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hash): self { $this->password = $hash; return $this; }

    public function getRoles(): array
    {
        // ROLE_USER toujours présent
        return array_values(array_unique([...$this->roles, self::ROLE_USER]));
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getAgence(): ?string { return $this->agence; }
    public function setAgence(string $agence): self { $this->agence = $agence; return $this; }

    public function eraseCredentials(): void {}
}
