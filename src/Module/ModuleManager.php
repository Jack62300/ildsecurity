<?php
namespace App\Module;

use App\Entity\Extension;
use Doctrine\ORM\EntityManagerInterface;

class ModuleManager
{
    public function __construct(private EntityManagerInterface $em) {}
    

    public function isEnabled(string $code): bool
    {
        $ext = $this->em->getRepository(Extension::class)->find($code);
        return $ext?->isEnabled() ?? false;
    }

    public function setEnabled(string $code, bool $enabled): void
    {
        $repo = $this->em->getRepository(Extension::class);
        $ext = $repo->find($code) ?? new Extension($code, strtoupper($code), false);
        $enabled ? $ext->enable() : $ext->disable();
        $this->em->persist($ext); $this->em->flush();
    }
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresModule
{
    public function __construct(public string $code) {}
}
