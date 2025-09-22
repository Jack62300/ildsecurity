<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'app_extension')]
class Extension
{
    #[ORM\Id, ORM\Column(length: 64)]
    private string $code; // ex: "fleet"

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(length: 128)]
    private string $label;

    public function __construct(string $code, string $label, bool $enabled = false) {
        $this->code = $code; $this->label = $label; $this->enabled = $enabled;
    }

    public function getCode(): string { return $this->code; }
    public function isEnabled(): bool { return $this->enabled; }
    public function enable(): void { $this->enabled = true; }
    public function disable(): void { $this->enabled = false; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $l): void { $this->label = $l; }
}
