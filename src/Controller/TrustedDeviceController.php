<?php
namespace App\Controller;

use App\Entity\TrustedDevice;
use App\Repository\TrustedDeviceRepository;
use App\Security\TrustedDeviceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class TrustedDeviceController extends AbstractController
{
    #[Route('/device/approve/{id}/{token}', name: 'device_approve', methods: ['GET'])]
    public function approve(int $id, string $token, TrustedDeviceManager $manager): Response
    {
        $ok = $manager->approve($id, $token);

        return $this->render('security/device_approve_result.html.twig', [
            'success' => $ok,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices', name: 'devices_index', methods: ['GET'])]
    public function index(TrustedDeviceRepository $repo): Response
    {
        $devices = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('devices/index.html.twig', [
            'devices' => $devices,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices/{id}/approve', name: 'devices_approve_admin', methods: ['POST','GET'])]
    public function approveAdmin(TrustedDevice $device, EntityManagerInterface $em): Response
    {
        if ($device->isApproved()) {
            $this->addFlash('info', 'Cet appareil est déjà approuvé.');
            return $this->redirectToRoute('devices_index');
        }

        $device->approve();
        $em->flush();

        $this->addFlash('success', 'Appareil approuvé avec succès.');
        return $this->redirectToRoute('devices_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices/{id}/delete', name: 'devices_delete', methods: ['POST'])]
    public function delete(TrustedDevice $device, Request $request, EntityManagerInterface $em): Response
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_device_'.$device->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('devices_index');
        }

        $em->remove($device);
        $em->flush();

        $this->addFlash('success', 'Appareil supprimé.');
        return $this->redirectToRoute('devices_index');
    }

    /**
     * (Facultatif) route de test admin pour déclencher un email sur l’IP/UA courants.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices/request-approval', name: 'devices_request_approval_test', methods: ['GET'])]
    public function requestApprovalTest(TrustedDeviceManager $manager, Request $request): Response
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException('Utilisateur requis.');
        }

        $ip  = $request->getClientIp() ?? '0.0.0.0';
        $ua  = $request->headers->get('User-Agent');

        $manager->createOrSendPending($user, $ip, $ua);

        $this->addFlash('success', 'Demande envoyée (MAIL_TRUSTED_DEVICE).');
        return $this->redirectToRoute('devices_index');
    }

    #[Route('/_mail-test', name: '_mail_test')]
    public function mailTest(\Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
        $to = getenv('MAIL_TRUSTED_DEVICE') ?: 'devunity62400@gmail.com';
        try {
            $email = (new \Symfony\Component\Mime\Email())
                ->from(getenv('MAIL_FROM') ?: 'contact@toplegends.fr')
                ->to($to)
                ->subject('Test SMTP LWS')
                ->text('Bonjour, test SMTP depuis Symfony.');

            $mailer->send($email);
            return new Response('OK: envoyé à '.$to);
        } catch (\Throwable $e) {
            return new Response('Mailer error: '.$e->getMessage(), 500);
        }
    }

    #[Route('/_mail-diag', name: '_mail_diag')]
    public function __invoke(): Response
    {
        $dsn   = getenv('MAILER_DSN') ?: '';
        $from  = getenv('MAIL_FROM') ?: '';
        $to    = getenv('MAIL_TRUSTED_DEVICE') ?: 'devunity62400@gmail.com';

        // Masque le mot de passe dans le DSN pour affichage
        $dsnMasked = preg_replace('#(://[^:]+:)([^@]+)(@)#', '$1********$3', $dsn ?? '');

        $out = [];
        $out[] = 'MAILER_DSN = '.$dsnMasked;
        $out[] = 'MAIL_FROM  = '.$from;
        $out[] = 'TO (test)  = '.$to;

        try {
            $transport = Transport::fromDsn($dsn);
            // Teste la connexion/auth TLS avant l’envoi
            $transport->start();
            $out[] = '[OK] transport->start()';
        } catch (\Throwable $e) {
            $out[] = '[ERR] transport->start(): '.$e->getMessage();
            return new Response(implode("\n", $out), 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        try {
            $mailer = new Mailer($transport);
            $email = (new Email())
                ->from($from ?: 'contact@toplegends.fr') // ← DOIT être l’adresse de la boîte LWS
                ->to($to)
                ->subject('Diag SMTP depuis Symfony')
                ->text("Test simple.\nHeure=".date('c'));

            $mailer->send($email);
            $out[] = '[OK] mailer->send()';
            return new Response(implode("\n", $out), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        } catch (\Throwable $e) {
            $out[] = '[ERR] mailer->send(): '.$e->getMessage();
            return new Response(implode("\n", $out), 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }
}


