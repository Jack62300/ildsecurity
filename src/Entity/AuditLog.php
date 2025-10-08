<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_audit_user_identifier', columns: ['user_identifier'])]
#[ORM\Index(name: 'idx_audit_route', columns: ['route'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    // email / username / id (pas de relation pour éviter les cascades)
    #[ORM\Column(name: 'user_identifier', type: 'string', length: 180, nullable: true)]
    private ?string $userIdentifier = null;

    #[ORM\Column(name: 'method', type: 'string', length: 20)]
    private string $method = 'GET';

    #[ORM\Column(name: 'route', type: 'string', length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(name: 'path', type: 'string', length: 1024, nullable: true)]
    private ?string $path = null;

    // HMAC de l’IP (RGPD-friendly)
    #[ORM\Column(name: 'ip_hash', type: 'string', length: 128, nullable: true)]
    private ?string $ipHash = null;

    #[ORM\Column(name: 'user_agent', type: 'string', length: 255, nullable: true)]
    private ?string $userAgent = null;

    // create | update | delete | approve | reject | login_success | login_failure | visit | ...
    #[ORM\Column(name: 'action', type: 'string', length: 50)]
    private string $action;

    // Nom d’entité ou contexte libre
    #[ORM\Column(name: 'object_type', type: 'string', length: 255, nullable: true)]
    private ?string $objectType = null;

    // ID de l’entité (texte pour flexibilité)
    #[ORM\Column(name: 'object_id', type: 'string', length: 255, nullable: true)]
    private ?string $objectId = null;

    // Données JSON (avant/après ou payload)
    #[ORM\Column(name: 'payload', type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------------------- Getters / Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function setIpHash(?string $ipHash): self
    {
        $this->ipHash = $ipHash;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectType(?string $objectType): self
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    public function setObjectId(?string $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }
}
