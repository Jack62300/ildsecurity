<?php

namespace App\Repository;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    public function getNextBonNumero(): string
    {
        // On récupère le max parmi des valeurs déjà **paddées sur 6**,
        // la comparaison lexicographique convient.
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.bonNumero) AS maxnum')
            ->getQuery()
            ->getSingleScalarResult();

        if (!$max) {
            return '000001';
        }
        $next = (int)$max + 1;
        return str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}
