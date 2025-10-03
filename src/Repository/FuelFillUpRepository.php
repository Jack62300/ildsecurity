<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FuelFillUp;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FuelFillUp>
 */
final class FuelFillUpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuelFillUp::class);
    }

    /**
     * Historique d’un véhicule (du plus récent au plus ancien).
     *
     * @return array<FuelFillUp>
     */
    public function findByVehicle(Vehicle $vehicle): array
    {
        /** @var array<FuelFillUp> $rows */
        $rows = $this->createQueryBuilder('f')
            ->andWhere('f.vehicle = :v')->setParameter('v', $vehicle)
            ->orderBy('f.filledAt', 'DESC')
            ->getQuery()->getResult();

        return $rows;
    }

    /**
     * Plein précédent d’un véhicule avant une date donnée (si $before = null, le plus récent strictement antérieur n’existe pas → retourne le plus récent).
     */
    public function findPreviousForVehicle(Vehicle $vehicle, ?\DateTimeInterface $before = null): ?FuelFillUp
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.vehicle = :v')->setParameter('v', $vehicle)
            ->orderBy('f.filledAt', 'DESC')
            ->setMaxResults(1);

        if ($before !== null) {
            $qb->andWhere('f.filledAt < :b')->setParameter('b', $before);
        }

        /** @var FuelFillUp|null $res */
        $res = $qb->getQuery()->getOneOrNullResult();
        return $res;
    }

    /**
     * Alias rétro-compat pour du code existant.
     */
    public function findPrevForVehicle(Vehicle $vehicle, ?\DateTimeInterface $before = null): ?FuelFillUp
    {
        return $this->findPreviousForVehicle($vehicle, $before);
    }

    /**
     * Version “compteur” : précédent strictement en-dessous d’un odomètre donné.
     */
    public function findPreviousOdometerForVehicle(Vehicle $vehicle, int $currentOdometer): ?FuelFillUp
    {
        /** @var FuelFillUp|null $res */
        $res = $this->createQueryBuilder('f')
            ->andWhere('f.vehicle = :vehicle')
            ->andWhere('f.odometer < :currentOdometer')
            ->andWhere('f.odometer IS NOT NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('currentOdometer', $currentOdometer)
            ->orderBy('f.odometer', 'DESC')   // le plus proche en dessous
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $res;
    }

    /**
     * Pleins d’une période + statut optionnel.
     *
     * @return array<FuelFillUp>
     */
    public function findForPeriod(?\DateTimeImmutable $from, ?\DateTimeImmutable $to, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.vehicle', 'v')->addSelect('v')
            ->orderBy('f.filledAt', 'DESC');

        if ($from !== null) {
            $qb->andWhere('f.filledAt >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('f.filledAt < :to')->setParameter('to', $to);
        }
        if ($status !== null && $status !== '') {
            $qb->andWhere('f.status = :s')->setParameter('s', $status);
        }

        /** @var array<FuelFillUp> $rows */
        $rows = $qb->getQuery()->getResult();
        return $rows;
    }
}
