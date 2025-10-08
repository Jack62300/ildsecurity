<?php
namespace App\Entity;

use App\Security\Auditable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FuelFillUpRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FuelFillUpRepository::class)]
#[ORM\Table(name: "fuel_fillup")]
#[Auditable]
class FuelFillUp
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'fillUps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $filledAt = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $odometer = 0; // km au moment du plein

    #[ORM\Column(type: 'decimal', precision: 8, scale: 3)]
    #[Assert\Positive]
    private string $pricePerLitre = '0.000'; // € / L

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\Positive]
    private string $liters = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalPrice = '0.00'; // calculé = liters * pricePerLitre

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $distanceKm = null; // calculé = odometer - odometer(previous)

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending'; // pending, validated, rejected


    public function __construct()
    {
        $this->filledAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'pending';
    }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isValidated(): bool { return $this->status === 'validated'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function getId(): ?int { return $this->id; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $v): self { $this->vehicle = $v; return $this; }

    public function getFilledAt(): ?\DateTimeImmutable { return $this->filledAt; }
    public function setFilledAt(\DateTimeImmutable $d): self { $this->filledAt = $d; return $this; }

    public function getOdometer(): int { return $this->odometer; }
    public function setOdometer(int $km): self { $this->odometer = $km; return $this; }

    public function getPricePerLitre(): string { return $this->pricePerLitre; }
    public function setPricePerLitre(string $p): self { $this->pricePerLitre = $p; return $this; }

    public function getLiters(): string { return $this->liters; }
    public function setLiters(string $l): self { $this->liters = $l; return $this; }

    public function getTotalPrice(): string { return $this->totalPrice; }
    public function setTotalPrice(string $t): self { $this->totalPrice = $t; return $this; }

    public function getDistanceKm(): ?int { return $this->distanceKm; }
    public function setDistanceKm(?int $d): self { $this->distanceKm = $d; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): self { $this->notes = $n; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): self { $this->createdAt = $d; return $this; }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Validé',
            'rejected' => 'Refusé',
            default => 'Inconnu'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => '#f59e0b',    // orange
            'validated' => '#10b981',  // vert
            'rejected' => '#ef4444',   // rouge
            default => '#6b7280'       // gris
        };
    }
}
