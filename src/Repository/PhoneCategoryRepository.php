<?php
namespace App\Repository;

use App\Entity\PhoneCategory;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhoneCategory>
 */
final class PhoneCategoryRepository extends ServiceEntityRepository
{
     public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhoneCategory::class);
    }
}
