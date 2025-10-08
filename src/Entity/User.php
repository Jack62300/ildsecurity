<?php
namespace App\Entity;

use App\Security\Auditable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet e-mail est déjà utilisé.')]
#[Auditable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Rôles disponibles
    public const ROLE_ADMIN     = 'ROLE_ADMIN';
    public const ROLE_DEV       = 'ROLE_DEV';
    public const ROLE_SUPPORT   = 'ROLE_SUPPORT';
    public const ROLE_OPERATEUR = 'ROLE_OPERATEUR';
    public const ROLE_USER      = 'ROLE_USER';

    /** Priorité d’affichage/sélection si plusieurs rôles existent déjà */
    private const ROLE_PRIORITY = [
        self::ROLE_ADMIN,
        self::ROLE_DEV,
        self::ROLE_SUPPORT,
        self::ROLE_OPERATEUR,
    ];

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

    /** Toujours inclure ROLE_USER */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, self::ROLE_USER]));
    }

    /** Conserve au plus 1 rôle “maître” (hors ROLE_USER) */
    public function setRoles(array $roles): self
    {
        $roles = array_values(array_unique(array_filter($roles, fn($r) => $r !== self::ROLE_USER)));
        // garde seulement le premier si plusieurs passés
        $this->roles = array_slice($roles, 0, 1);
        return $this;
    }

    /** Propriété VIRTUELLE pour le form (un seul rôle) */
    public function getPrimaryRole(): ?string
    {
        // choisit le rôle selon la priorité si jamais plusieurs existent
        $current = array_filter($this->roles, fn($r) => $r !== self::ROLE_USER);
        foreach (self::ROLE_PRIORITY as $role) {
            if (in_array($role, $current, true)) {
                return $role;
            }
        }
        return $current ? array_values($current)[0] : null;
    }

    /** Remplace l’ensemble des rôles par un seul rôle maître */
    public function setPrimaryRole(?string $role): self
    {
        if ($role && $role !== self::ROLE_USER) {
            $this->roles = [$role];
        } else {
            $this->roles = []; // ROLE_USER sera ajouté par getRoles()
        }
        return $this;
    }

    public function getAgence(): ?string { return $this->agence; }
    public function setAgence(string $agence): self { $this->agence = $agence; return $this; }

    #[\Deprecated(reason: 'eraseCredentials() n’est plus utilisé depuis Symfony 7.3')]
    public function eraseCredentials(): void {}
}
