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

class TrustedDeviceManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TrustedDeviceRepository $repo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGen,
        private readonly array $trustedIps = [],
        private readonly string $fromEmail = 'no-reply@example.com',
        private readonly ?string $approvalRecipientEmail = null,
        private readonly int $ttlHours = 24,
    ) {}

    public function isTrustedIp(string $ip): bool
    {
        return in_array($ip, $this->trustedIps, true);
    }

    public function isApproved(User $user, string $ip): bool
    {
        if ($this->isTrustedIp($ip)) {
            return true;
        }
        return (bool) $this->repo->findApprovedByUserAndIp($user, $ip);
    }

    /**
     * Appelé quand l’appareil n’est pas reconnu : crée/refresh une demande
     * et envoie un email à MAIL_TRUSTED_DEVICE avec le lien d’approbation.
     */
    public function createOrSendPending(User $user, string $ip, ?string $userAgent = null): void
    {
        if ($this->isTrustedIp($ip)) {
            return;
        }

        $pending = $this->repo->findPendingByUserAndIp($user, $ip)
            ?? (new TrustedDevice())
                ->setUser($user)
                ->setIp($ip)
                ->setUserAgent($userAgent);

        $rawToken = bin2hex(random_bytes(32));
        $pending->setApprovalTokenHash(hash('sha256', $rawToken));
        $pending->setExpiresAt((new \DateTimeImmutable())->modify("+{$this->ttlHours} hours"));

        $this->em->persist($pending);
        $this->em->flush();

        $this->sendApprovalEmail($user, $pending, $rawToken);
    }

    private function sendApprovalEmail(User $user, TrustedDevice $device, string $rawToken): void
    {
        $to = $this->approvalRecipientEmail;
        if (!$to) {
            return; // pas de destinataire → on n’envoie pas
        }

        $approveUrl = $this->urlGen->generate('device_approve', [
            'id'    => $device->getId(),
            'token' => $rawToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $ip = $device->getIp() ?? 'n/a';
        $ua = $device->getUserAgent() ?? 'n/a';

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($to)
            ->subject('Validation d’un nouvel appareil')
            ->html(<<<HTML
                <p>Un nouvel appareil a tenté de se connecter au compte de <strong>{$user->getUserIdentifier()}</strong>.</p>
                <ul>
                  <li><strong>IP :</strong> {$ip}</li>
                  <li><strong>User-Agent :</strong> {$ua}</li>
                </ul>
                <p>Pour approuver cet appareil :</p>
                <p><a href="{$approveUrl}">👉 Valider cet appareil</a></p>
                <p>Ce lien expire dans {$this->ttlHours} heures.</p>
            HTML);

        $this->mailer->send($email);
    }

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

        $device->approve(); // marque approuvé et purge token si prévu dans l’entité
        $this->em->flush();

        return true;
    }
}
