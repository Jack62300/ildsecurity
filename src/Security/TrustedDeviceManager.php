<?php
// src/Security/TrustedDeviceManager.php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\TrustedDevice;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TrustedDeviceRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrustedDeviceManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TrustedDeviceRepository $repo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGen,
        private readonly array $trustedIps = [],
        private readonly string $fromEmail = 'contact@toplegends.fr',
        private readonly ?string $approvalRecipientEmail = null,
        private readonly int $ttlHours = 24,
        private readonly string $pepper = '', // injecter via %env(APP_IP_PEPPER)%
    ) {}

    private function hashStable(string $val): string
    {
        // HMAC-SHA256 -> base64url (non réversible). Pepper injecté via env.
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $val, $this->pepper, true)), '+/', '-_'), '=');
    }

    public function isTrustedIp(string $ip): bool
    {
        return in_array($ip, $this->trustedIps, true);
    }

    /**
     * Vérifie si, pour cet utilisateur, il existe un device approuvé
     * pour l'IP fournie (comparaison sur HMAC).
     */
    public function isIpTrusted(User $user, string $ip): bool
    {
        if ($ip === '') {
            return false;
        }
        if ($this->isTrustedIp($ip)) {
            return true;
        }
        $hash = $this->hashStable($ip);
        return $this->repo->existsApprovedByUserAndDeviceHash($user, $hash);
    }

    /**
     * Crée ou refresh une demande en attente basée sur le HMAC de l'IP,
     * et envoie l'email d'approbation. Ne stocke jamais l'IP en clair.
     */
    public function createOrSendPending(User $user, string $ip, ?string $userAgent = null): void
    {
        if ($this->isTrustedIp($ip)) {
            return;
        }

        $deviceHash = $this->hashStable($ip);

        // réutiliser une demande en attente si elle existe
        $pending = $this->repo->findPendingByUserAndDeviceHash($user, $deviceHash);

        if (!$pending) {
            $pending = (new TrustedDevice())
                ->setUser($user)
                ->setDeviceIdHash($deviceHash)
                ->setUserAgent($userAgent);
            $this->em->persist($pending);
        } else {
            $pending->setUserAgent($userAgent);
        }

        // token d'approbation (hashé avant stockage)
        $rawToken = bin2hex(random_bytes(32));
        $pending->setApprovalTokenHash(hash('sha256', $rawToken));
        $pending->setExpiresAt((new \DateTimeImmutable())->modify("+{$this->ttlHours} hours"));

        $this->em->flush();

        $this->sendApprovalEmail($user, $pending, $rawToken, $deviceHash);
    }

    /**
     * Envoie l'email d'approbation. Ne met PAS l'IP en clair dans l'email.
     * On peut afficher un extrait du hash (ex: 12 premiers chars) pour repérage.
     */
    private function sendApprovalEmail(User $user, TrustedDevice $device, string $rawToken, string $deviceHash): void
    {
        $to   = $this->approvalRecipientEmail ?: (getenv('MAIL_TRUSTED_DEVICE') ?: null);
        $from = $this->fromEmail ?: (getenv('MAIL_FROM') ?: 'noreply@example.com');
        if (!$to) { return; }

        $approveUrl = $this->urlGen->generate('device_approve', [
            'id'    => $device->getId(),
            'token' => $rawToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = [
            'appName'        => 'ILD Security',
            'approveUrl'     => $approveUrl,
            // on affiche le hash abrégé pour l'admin/utilisateur, jamais l'IP
            'deviceHashShort'=> substr($deviceHash, 0, 12) . '…',
            'ua'             => $device->getUserAgent() ?? 'n/a',
            'userIdentifier' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : ($user->getEmail() ?? 'Utilisateur'),
            'ttlHours'       => $this->ttlHours,
            'supportEmail'   => 'devunity62400@gmail.com',
            'logoUrl'        => $this->urlGen->getContext()->getScheme().'://'.$this->urlGen->getContext()->getHost().'/assets/images/logo.png',
        ];

        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject('Validation d’un nouvel appareil')
            ->htmlTemplate('email/trusted_device_approval.html.twig')
            ->textTemplate('email/trusted_device_approval.txt.twig')
            ->context($context);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('TrustedEmail error: '.$e->getMessage());
        }
    }

    /**
     * Approuve la demande si token valide. On n'a pas besoin d'un rawDeviceId ici,
     * car le deviceHash (HMAC de l'IP) est déjà stocké en base.
     */
    public function approve(int $id, string $rawToken): bool
    {
        /** @var TrustedDevice|null $device */
        $device = $this->em->getRepository(TrustedDevice::class)->find($id);
        if (!$device || $device->isApproved()) {
            return false;
        }

        $expiresAt = $device->getExpiresAt();
        if ($expiresAt instanceof \DateTimeImmutable && $expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        $expected = $device->getApprovalTokenHash();
        if (!$expected || !hash_equals($expected, hash('sha256', $rawToken))) {
            return false;
        }

        

        $device->approve();
        $this->em->flush();

        return true;
    }
}
