<?php 
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

final class EntityCatalog
{
    public function __construct(
        private EntityManagerInterface $em,
        /** @var array<string,string> FQCN => Label */
        private array $labels = [],
    ) {}

    /** [label => FQCN] uniquement sur les entités listées dans la map */
    public function listExportables(): array
    {
        $choices = [];
        foreach ($this->labels as $fqcn => $label) {
            // vérifie que l’entité existe bien côté Doctrine
            if (!class_exists($fqcn)) continue;
            $choices[$label] = $fqcn; // ✅ label pro
        }
        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);
        return $choices;
    }

    /** Par défaut : tout cocher (ou restreins ici si besoin) */
    public function defaultSelected(): array
    {
        return array_values($this->labels);
    }
}
