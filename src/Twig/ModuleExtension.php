<?php
namespace App\Twig;

use App\Repository\ExtensionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(private ExtensionRepository $repo) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('module_enabled', [$this, 'moduleEnabled']),
        ];
    }

    public function moduleEnabled(string $code): bool
    {
        $ext = $this->repo->findOneBy(['label' => $code]); // ou ['code' => $code] selon ton entité
        return (bool) ($ext?->isEnabled());
    }
}
