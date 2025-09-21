<?php
namespace App\Entity;

use App\Repository\TrustedDeviceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrustedDeviceRepository::class)]
class TrustedDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 45)]
    private string $ip = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    // ✅ booléen d’approbation
    #[ORM\Column(options: ['default' => false])]
    private bool $approved = false;

    // ✅ date de création (manquante chez toi)
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // optionnels mais utiles
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $approvalTokenHash = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); // ✅ initialise à maintenant
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getIp(): string { return $this->ip; }
    public function setIp(string $ip): self { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $ua): self { $this->userAgent = $ua; return $this; }

    public function isApproved(): bool { return $this->approved; }
    public function approve(): self
    {
        $this->approved = true;
        $this->approvedAt = new \DateTimeImmutable();
        $this->approvalTokenHash = null;
        $this->expiresAt = null;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable { return $this->approvedAt; }

    public function getApprovalTokenHash(): ?string { return $this->approvalTokenHash; }
    public function setApprovalTokenHash(?string $hash): self { $this->approvalTokenHash = $hash; return $this; }

    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeImmutable $e): self { $this->expiresAt = $e; return $this; }

    // ✅ getters/setters de createdAt
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }
}
