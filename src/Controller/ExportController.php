<?php
// src/Controller/ExportController.php
namespace App\Controller;

use App\Form\ExportFormType;
use App\Service\ExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')] // ✅ granted admin
final class ExportController extends AbstractController
{
    #[Route('/admin/export', name: 'app_export', methods: ['GET','POST'])] // ✅ route admin
    public function __invoke(Request $request, ExportService $exportService)
    {
        $form = $this->createForm(ExportFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data  = $form->getData();
            $fqcn  = $data['entities'] ?? [];
            $delim = $data['csv_delimiter'] ?: ';';
            $limit = (int)($data['limit'] ?? 0);

            return $exportService->exportCsv($fqcn, $delim, $limit);
        }

        return $this->render('export/index.html.twig', ['form' => $form->createView()]);
    }
}
