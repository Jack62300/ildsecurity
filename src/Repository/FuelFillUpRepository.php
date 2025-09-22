<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\FuelFillUp;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class FuelFillUpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuelFillUp::class);
    }

    /**
     * Dernier plein pour un véhicule avant une date donnée (ou le plus récent si null)
     */
    public function findPreviousForVehicle(Vehicle $vehicle, ?\DateTimeImmutable $before = null): ?FuelFillUp
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.vehicle = :v')
            ->andWhere('f.status != :status')
            ->setParameter('v', $vehicle)
            ->setParameter('status', 'validated')
            ->orderBy('f.filledAt', 'DESC')
            ->setMaxResults(1);

        if ($before) {
            $qb->andWhere('f.filledAt < :before')->setParameter('before', $before);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function createNotValidatedQb(): QueryBuilder
        {
            return $this->createQueryBuilder('f')
                ->andWhere('COALESCE(f.status, \'\') <> :validated')
                ->setParameter('validated', 'validated')
                ->orderBy('f.filledAt', 'DESC');
        }
}
