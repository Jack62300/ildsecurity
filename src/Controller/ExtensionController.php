<?php
namespace App\Controller;

use App\Entity\Extension;
use App\Module\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/extensions')]
class ExtensionController extends AbstractController
{
    #[Route('', name:'admin_extensions')]
    public function index(\Doctrine\ORM\EntityManagerInterface $em): Response
    {
        $list = $em->getRepository(Extension::class)->findAll();
        return $this->render('admin/extensions.html.twig', ['extensions' => $list]);
    }

    #[Route('/toggle/{code}', name:'admin_extensions_toggle', methods:['POST'])]
    public function toggle(string $code, Request $req, ModuleManager $mm): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $mm->setEnabled($code, $req->request->getBoolean('enabled'));
        return $this->redirectToRoute('admin_extensions');
    }

    
}
