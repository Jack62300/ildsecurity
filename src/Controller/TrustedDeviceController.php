<?php
namespace App\Controller;

use App\Entity\TrustedDevice;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use App\Security\TrustedDeviceManager;
use Symfony\Component\Mailer\Transport;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TrustedDeviceRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrustedDeviceController extends AbstractController
{
   #[Route('/device/approve', name: 'device_approve')]
    public function approve(Request $request, TrustedDeviceManager $devices): Response
    {
        $id  = (int) $request->query->get('id', 0);
        $tok = (string) $request->query->get('token', '');

        if ($id && $tok && $devices->approve($id, $tok)) {
            $resp = new RedirectResponse($this->generateUrl('app_login'));
            $this->addFlash('success', 'Appareil approuvé. Vous pouvez vous connecter.');
            return $resp;
        }

        $this->addFlash('danger', 'Lien invalide ou expiré.');
        return $this->redirectToRoute('app_login');
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
}


