<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\PhoneCategory;
use App\Entity\PhoneNumber;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PhoneNumber> */
final class PhoneNumberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhoneNumber::class);
    }
}
