<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ExportFormType;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ExportController extends AbstractController
{
    #[Route('/admin/export', name: 'app_export', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, ExportService $exportService): Response
    {
        $form = $this->createForm(ExportFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = (array) $form->getData();

            // entities: array<class-string>
            $fqcnRaw = $data['entities'] ?? [];
            /** @var array<class-string> $fqcn */
            $fqcn = [];
            if (\is_array($fqcnRaw)) {
                foreach ($fqcnRaw as $item) {
                    if (\is_string($item) && $item !== '') {
                        /** @var class-string $item */
                        $fqcn[] = $item;
                    }
                }
            }

            // csv_delimiter: string (fallback ;)
            $delim = ';';
            if (isset($data['csv_delimiter']) && \is_string($data['csv_delimiter']) && $data['csv_delimiter'] !== '') {
                $delim = $data['csv_delimiter'];
            }

            // limit: int (>=0) optionnel
            $limit = null;
            if (isset($data['limit'])) {
                $limit = \is_int($data['limit']) ? $data['limit'] : (int) $data['limit'];
                if ($limit < 0) {
                    $limit = 0;
                }
            }

            // Si ton ExportService n'accepte que (array, string), supprime $limit ici.
            return $exportService->exportCsv($fqcn, $delim, $limit ?? 0);
        }

        return $this->render('export/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
