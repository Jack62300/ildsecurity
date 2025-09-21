<?php
// src/Repository/TrustedDeviceRepository.php
namespace App\Repository;

use App\Entity\TrustedDevice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrustedDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrustedDevice::class);
    }

    public function findApprovedByUserAndIp(User $user, string $ip): ?TrustedDevice
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')->setParameter('u', $user)
            ->andWhere('d.ip = :ip')->setParameter('ip', $ip)
            ->andWhere('d.approved = true')
            ->getQuery()->getOneOrNullResult();
    }

    public function findPendingByUserAndIp(User $user, string $ip): ?TrustedDevice
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')->setParameter('u', $user)
            ->andWhere('d.ip = :ip')->setParameter('ip', $ip)
            ->andWhere('d.approved = false')
            ->getQuery()->getOneOrNullResult();
    }
}
