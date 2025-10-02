<?php

namespace App\Repository;

use App\Entity\ModificationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModificationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModificationRequest::class);
    }

    /** @return ModificationRequest[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.status = :s')->setParameter('s', ModificationRequest::STATUS_PENDING)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
