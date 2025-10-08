<?php
namespace App\Entity;

use App\Entity\User;
use App\Security\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TrustedDeviceRepository;

#[ORM\Entity(repositoryClass: TrustedDeviceRepository::class)]
#[ORM\Table(name: 'trusted_device')]
#[ORM\UniqueConstraint(name: 'uniq_user_device_hash', columns: ['user_id', 'device_id_hash'])]
#[Auditable]
class TrustedDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Hachage (HMAC-SHA256 + base64url) du device_id public posé en cookie.
     * On vise large pour la longueur (base64url de 32 bytes ~ 43-44 chars).
     */
    #[ORM\Column(name: 'device_id_hash', length: 128, nullable: true)]
    private ?string $deviceIdHash = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $approved = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    /**
     * Token d’approbation (hashé) valable jusqu’à expiresAt.
     * Purger/nuller après approve().
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $approvalTokenHash = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------- Getters / Setters --------

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getDeviceIdHash(): ?string { return $this->deviceIdHash; }
    public function setDeviceIdHash(?string $hash): self { $this->deviceIdHash = $hash; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $ua): self { $this->userAgent = $ua; return $this; }

    public function isApproved(): bool { return $this->approved; }
    public function getApprovedAt(): ?\DateTimeImmutable { return $this->approvedAt; }

    public function approve(): self
    {
        $this->approved = true;
        $this->approvedAt = new \DateTimeImmutable();
        $this->approvalTokenHash = null;
        $this->expiresAt = null;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }

    public function getLastSeenAt(): ?\DateTimeImmutable { return $this->lastSeenAt; }
    public function setLastSeenAt(?\DateTimeImmutable $dt): self { $this->lastSeenAt = $dt; return $this; }

    public function touchSeen(): self
    {
        $this->lastSeenAt = new \DateTimeImmutable();
        return $this;
    }

    public function getApprovalTokenHash(): ?string { return $this->approvalTokenHash; }
    public function setApprovalTokenHash(?string $hash): self { $this->approvalTokenHash = $hash; return $this; }

    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeImmutable $e): self { $this->expiresAt = $e; return $this; }
}
