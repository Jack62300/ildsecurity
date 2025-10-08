<?php
// src/Repository/TrustedDeviceRepository.php
namespace App\Repository;

use App\Entity\TrustedDevice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les appareils de confiance.
 * On n'utilise plus l'IP en clair : l'identifiant est deviceIdHash (HMAC de l'IP).
 *
 * @extends ServiceEntityRepository<TrustedDevice>
 */
class TrustedDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrustedDevice::class);
    }

    /**
     * Appareil approuvé correspondant à {user, deviceIdHash}, ou null.
     */
    public function findApprovedByUserAndDeviceHash(User $user, string $deviceIdHash): ?TrustedDevice
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')->setParameter('u', $user)
            ->andWhere('d.deviceIdHash = :h')->setParameter('h', $deviceIdHash)
            ->andWhere('d.approved = true')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * true s'il existe déjà un appareil approuvé {user, deviceIdHash}.
     */
    public function existsApprovedByUserAndDeviceHash(User $user, string $deviceIdHash): bool
    {
        return (bool) $this->createQueryBuilder('d')
            ->select('1')
            ->andWhere('d.user = :u')->setParameter('u', $user)
            ->andWhere('d.deviceIdHash = :h')->setParameter('h', $deviceIdHash)
            ->andWhere('d.approved = true')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Demande en attente (non approuvée) pour {user, deviceIdHash}, ou null.
     */
    public function findPendingByUserAndDeviceHash(User $user, string $deviceIdHash): ?TrustedDevice
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :u')->setParameter('u', $user)
            ->andWhere('d.deviceIdHash = :h')->setParameter('h', $deviceIdHash)
            ->andWhere('d.approved = false')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Crée ou met à jour une demande en attente pour {user, deviceIdHash}.
     * Ne flush pas (laisse l'appelant décider).
     */
    public function upsertPending(
        User $user,
        string $deviceIdHash,
        ?string $approvalTokenHash,
        \DateTimeImmutable $expiresAt,
        ?string $userAgent = null
    ): TrustedDevice {
        $pending = $this->findPendingByUserAndDeviceHash($user, $deviceIdHash);

        if (!$pending) {
            $pending = (new TrustedDevice())
                ->setUser($user)
                ->setDeviceIdHash($deviceIdHash);
            $this->_em->persist($pending);
        }

        $pending
            ->setUserAgent($userAgent)
            ->setApprovalTokenHash($approvalTokenHash)
            ->setExpiresAt($expiresAt);

        return $pending;
    }

    /**
     * Marque l'appareil comme "vu" maintenant (lastSeenAt = now). Flush optionnel.
     */
    public function touchSeen(TrustedDevice $device, bool $flush = false): void
    {
        $device->touchSeen();
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Purge les demandes en attente expirées (approved = false et expiresAt < now()).
     * Retourne le nombre de lignes supprimées.
     */
    public function purgeExpiredPendings(\DateTimeImmutable $now = new \DateTimeImmutable()): int
    {
        $qb = $this->createQueryBuilder('d')
            ->delete()
            ->andWhere('d.approved = false')
            ->andWhere('d.expiresAt IS NOT NULL')
            ->andWhere('d.expiresAt < :now')->setParameter('now', $now);

        return (int) $qb->getQuery()->execute();
    }

    /**
     * Purge les appareils approuvés jamais revus depuis un certain seuil (ex: 13 mois).
     * Si lastSeenAt est NULL, on se base sur (approvedAt || createdAt).
     */
    public function purgeStaleApproved(\DateTimeImmutable $threshold): int
    {
        $em = $this->getEntityManager();

        // Récupère d'abord les IDs à supprimer
        $ids = $this->createQueryBuilder('d')
            ->select('d.id')
            ->andWhere('d.approved = true')
            ->andWhere('
                (d.lastSeenAt IS NULL AND COALESCE(d.approvedAt, d.createdAt) < :t)
                OR (d.lastSeenAt IS NOT NULL AND d.lastSeenAt < :t)
            ')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->getScalarResult();

        if (empty($ids)) {
            return 0;
        }

        return (int) $em->createQuery('DELETE FROM App\Entity\TrustedDevice d WHERE d.id IN (:ids)')
            ->setParameter('ids', array_column($ids, 'id'))
            ->execute();
    }

    /* ----- Compat : anciennes méthodes IP (désactivées) ----- */

    /** @deprecated Remplacée par findApprovedByUserAndDeviceHash() */
    public function findApprovedByUserAndIp(User $user, string $ip): ?TrustedDevice
    {
        return null;
    }

    /** @deprecated Remplacée par findPendingByUserAndDeviceHash() */
    public function findPendingByUserAndIp(User $user, string $ip): ?TrustedDevice
    {
        return null;
    }
}
