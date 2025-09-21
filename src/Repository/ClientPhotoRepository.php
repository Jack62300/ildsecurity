<?php

namespace App\Repository;

use App\Entity\ClientPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientPhoto>
 *
 * @method ClientPhoto|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientPhoto|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientPhoto[]    findAll()
 * @method ClientPhoto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientPhoto::class);
    }

    // Ajoute tes méthodes custom ici si besoin
}
