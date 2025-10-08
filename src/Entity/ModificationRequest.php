<?php

namespace App\Entity;

use App\Security\Auditable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ModificationRequestRepository;

#[ORM\Entity(repositoryClass: ModificationRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Auditable]
class ModificationRequest
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Le client cible
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Client $client = null;

    // Données proposées (diff) sérialisées JSON
    #[ORM\Column(type: 'json')]
    private array $changes = [];

    // Infos optionnelles du soumettant (public)
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $submittedByName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $submittedByEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    // Optionnel si tu as une entité User
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $reviewedBy = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->status    ??= self::STATUS_PENDING;
    }

    // getters/setters ...
    public function getId(): ?int { return $this->id; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): self { $this->client = $client; return $this; }

    public function getChanges(): array { return $this->changes; }
    public function setChanges(array $changes): self { $this->changes = $changes; return $this; }

    public function getSubmittedByName(): ?string { return $this->submittedByName; }
    public function setSubmittedByName(?string $v): self { $this->submittedByName = $v; return $this; }

    public function getSubmittedByEmail(): ?string { return $this->submittedByEmail; }
    public function setSubmittedByEmail(?string $v): self { $this->submittedByEmail = $v; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $v): self { $this->comment = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): self { $this->createdAt = $v; return $this; }

    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeImmutable $v): self { $this->reviewedAt = $v; return $this; }

    public function getReviewedBy(): ?User { return $this->reviewedBy; }
    public function setReviewedBy(?User $u): self { $this->reviewedBy = $u; return $this; }
}
