<?php
// src/Controller/TrustedDeviceController.php
namespace App\Controller;

use App\Entity\TrustedDevice;
use Symfony\Component\Mime\Email;
use App\Security\TrustedDeviceManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TrustedDeviceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrustedDeviceController extends AbstractController
{
    /**
     * ✅ Route publique appelée depuis l'email
     * Valide un appareil si le token est correct (géré par TrustedDeviceManager)
     */
    #[Route('/device/approve/{id}/{token}', name: 'device_approve', methods: ['GET'])]
    public function approve(int $id, string $token, TrustedDeviceManager $manager): Response
    {
        $ok = $manager->approve($id, $token);

        return $this->render('security/device_approve_result.html.twig', [
            'success' => $ok,
        ]);
    }

    /**
     * ✅ Liste admin des appareils (en attente / approuvés)
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices', name: 'devices_index', methods: ['GET'])]
    public function index(TrustedDeviceRepository $repo): Response
    {
        $devices = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('devices/index.html.twig', [
            'devices' => $devices,
        ]);
    }

    /**
     * ✅ Validation admin d’un appareil (sans email)
     * Astuce : on réutilise la logique de l’entité (->approve()) et on purge le token.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/devices/{id}/approve', name: 'devices_approve_admin', methods: ['POST','GET'])]
    public function approveAdmin(TrustedDevice $device, EntityManagerInterface $em): Response
    {
        if ($device->isApproved()) {
            $this->addFlash('info', 'Cet appareil est déjà approuvé.');
            return $this->redirectToRoute('devices_index');
        }

        $device->approve(); // passe approved=true, approvedAt=now, et nettoie le token si tu l’as fait dans approve()
        $em->flush();

        $this->addFlash('success', 'Appareil approuvé avec succès.');
        return $this->redirectToRoute('devices_index');
    }

    /**
     * 🗑️ Suppression admin d’un appareil
     * Utilise un token CSRF pour éviter les suppressions accidentelles.
     */
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

    #[Route('/test-mail', name: 'test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from('noreply@test.local')
                ->to('user@example.com')
                ->subject('Test MailHog')
                ->text('Hello MailHog!');

            $mailer->send($email);
            return new Response('OK');
        } catch (\Throwable $e) {
            return new Response('Mailer error: '.$e->getMessage(), 500);
        }
    }
}
