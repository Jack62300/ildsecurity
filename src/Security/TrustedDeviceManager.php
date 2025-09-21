<?php
// src/Security/TrustedDeviceManager.php

declare(strict_types=1);

namespace App\Security;

use App\Entity\TrustedDevice;
use App\Entity\User;
use App\Repository\TrustedDeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Gère l’approbation des appareils (par IP) et l’envoi d’e-mails de validation.
 *
 * NOTE: Injecte la whitelist d’IP via services.yaml :
 *
 * parameters:
 *     trusted_ips: '%env(csv:TRUSTED_IPS)%'
 *
 * services:
 *     App\Security\TrustedDeviceManager:
 *         arguments:
 *             $trustedIps: '%trusted_ips%'
 */
class TrustedDeviceManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TrustedDeviceRepository $repo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGen,

        /** Liste d’IP autorisées (whitelist) injectée depuis l’env/config */
        private readonly array $trustedIps = [],

        /** Adresse expéditeur par défaut pour les e-mails de validation */
        private readonly string $fromEmail = 'no-reply@example.com',

        /** Durée de validité (heures) du lien d’approbation */
        private readonly int $ttlHours = 24,
    ) {}

    /**
     * Indique si l’IP est explicitement autorisée (whitelist).
     */
    public function isTrustedIp(string $ip): bool
    {
        // Comparaison stricte pour éviter les surprises
        return in_array($ip, $this->trustedIps, true);
    }

    /**
     * Retourne true si un appareil (IP) est déjà approuvé pour cet utilisateur,
     * OU si l’IP est dans la whitelist (bypass complet).
     */
    public function isApproved(User $user, string $ip): bool
    {
        // ✅ Bypass si l’IP est whitelistée
        if ($this->isTrustedIp($ip)) {
            return true;
        }

        return (bool) $this->repo->findApprovedByUserAndIp($user, $ip);
    }

    /**
     * Crée/renvoie une demande en attente + envoie l’e-mail de validation,
     * sauf si l’IP est whitelistée (dans ce cas, on ne fait rien).
     */
    public function createOrSendPending(User $user, string $ip, ?string $userAgent = null): void
    {
        // ✅ Rien à faire si l’IP est whitelistée
        if ($this->isTrustedIp($ip)) {
            return;
        }

        $pending = $this->repo->findPendingByUserAndIp($user, $ip)
            ?? (new TrustedDevice())
                ->setUser($user)
                ->setIp($ip)
                ->setUserAgent($userAgent);

        // Génère un token et le stocke hashé
        $rawToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);
        $pending->setApprovalTokenHash($hash);
        $pending->setExpiresAt((new \DateTimeImmutable())->modify("+{$this->ttlHours} hours"));

        $this->em->persist($pending);
        $this->em->flush();

        // Lien de validation (id + token brut)
        $url = $this->urlGen->generate('device_approve', [
            'id'    => $pending->getId(),
            'token' => $rawToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Validation d’un nouvel appareil')
            ->html(<<<HTML
                <p>Bonjour {$user->getUsername()},</p>
                <p>Une tentative de connexion a été détectée depuis l’adresse IP <strong>{$ip}</strong>.</p>
                <p>Si c’est bien vous, cliquez ici pour autoriser cet appareil :</p>
                <p><a href="{$url}">Autoriser cet appareil</a></p>
                <p>Ce lien expire dans {$this->ttlHours} heures.</p>
            HTML);

        $this->mailer->send($email);
    }

    /**
     * Valide l’appareil si le token est correct et non expiré.
     * Retourne true si l’approbation a réussi.
     */
    public function approve(int $id, string $rawToken): bool
    {
        /** @var TrustedDevice|null $device */
        $device = $this->em->getRepository(TrustedDevice::class)->find($id);
        if (!$device || $device->isApproved()) {
            return false;
        }

        // Expiration
        $expiresAt = $device->getExpiresAt();
        if ($expiresAt instanceof \DateTimeImmutable && $expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        // Vérification du token
        $expected = $device->getApprovalTokenHash();
        if (!$expected || !hash_equals($expected, hash('sha256', $rawToken))) {
            return false;
        }

        $device->approve();
        $this->em->flush();

        return true;
    }
}
