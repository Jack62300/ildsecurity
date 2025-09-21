<?php
// src/Repository/AllowedNetworkRepository.php
namespace App\Repository;

use App\Entity\AllowedNetwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AllowedNetworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AllowedNetwork::class);
    }

    /** @return AllowedNetwork[] */
    public function findAllActive(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.enabled = :enabled')->setParameter('enabled', true)
            ->andWhere('a.expiresAt IS NULL OR a.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
