<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Client::class); }

    
    /**
     * Recherche plein-texte + filtre initial A-Z sur "nom".
     *
     * @return Client[]
     */
   public function searchClients(?string $q, ?string $letter, int $limit = 48): array
{
    $qb = $this->createQueryBuilder('c');

    if ($q) {
        $q = mb_strtolower($q);

        $qb->andWhere('
            LOWER(c.nom)         LIKE :q OR
            LOWER(c.description) LIKE :q OR
            LOWER(c.adresse)     LIKE :q OR
            LOWER(c.information) LIKE :q OR
            LOWER(c.key)         LIKE :q OR
            LOWER(c.keycodeild)  LIKE :q OR
            CONCAT(c.codeAlarme, \'\') LIKE :q OR
            CONCAT(c.codetls, \'\')     LIKE :q
        ')
        ->setParameter('q', '%'.$q.'%');
    }

    // Filtre A-Z / non alphabétique (#)
    if ($letter === '#') {
        // DQL ne gère pas REGEXP : on contourne
        $qb->andWhere("SUBSTRING(LOWER(c.nom), 1, 1) NOT BETWEEN 'a' AND 'z'");
    } elseif ($letter !== null && $letter !== '') {
        $qb->andWhere('LOWER(c.nom) LIKE :first')
           ->setParameter('first', mb_strtolower($letter).'%');
    }

    return $qb->orderBy('c.nom', 'ASC')
              ->setMaxResults($limit)
              ->getQuery()
              ->getResult();
}

public function countClientsWithKey(): int
{
    return (int) $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->andWhere('c.key IS NOT NULL')
        ->andWhere("TRIM(c.key) <> ''")
        ->getQuery()
        ->getSingleScalarResult();
}

    //    /**
    //     * @return Client[] Returns an array of Client objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Client
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
