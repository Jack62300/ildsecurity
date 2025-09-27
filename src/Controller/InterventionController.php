<?php

namespace App\Controller;

use App\Entity\Intervention;
use App\Form\InterventionType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\InterventionRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/intervention')]
class InterventionController extends AbstractController
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {}

    private function signaturesDir(): string
    {
        return $this->projectDir . '/public/uploads/signatures';
    }


    #[Route('/', name: 'app_intervention_index', methods: ['GET'])]
    public function index(InterventionRepository $repo): Response
    {
        return $this->render('intervention/index.html.twig', [
            'interventions' => $repo->findAll(),
        ]);
    }


    #[Route('/new', name: 'app_intervention_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        InterventionRepository $repo
    ): Response {
        $intervention = new Intervention();

        // 1) Numéro à afficher dans le formulaire
        $provisional = $repo->getNextBonNumero();

        // 2) Passe la valeur au form via l’option "provisional_bon"
        $form = $this->createForm(InterventionType::class, $intervention, [
            'provisional_bon' => $provisional,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // (A) Numéro unique, regénéré à l’enregistrement
            $intervention->setBonNumero($repo->getNextBonNumero());

            // (B) >>> SAUVEGARDE SIGNATURE (canvas -> PNG) <<<
            $dataUrl = (string) $form->get('signature_draw')->getData();
            if ($dataUrl) {
                $publicPath = $this->saveSignatureFromDataUrl(
                    $dataUrl,
                    $this->signaturesDir()
                );
                $intervention->setSignaturePath($publicPath);
            }

            try {
                $em->persist($intervention);
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                // Collision rare (concurrence) => on recalcule une fois
                $intervention->setBonNumero($repo->getNextBonNumero());
                $em->flush();
            }

            return $this->redirectToRoute('app_intervention_index');
        }

        return $this->render('intervention/new.html.twig', [
            'intervention' => $intervention,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_intervention_show', methods: ['GET'])]
    public function show(Intervention $intervention): Response
    {
        return $this->render('intervention/show.html.twig', [
            'intervention' => $intervention,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_intervention_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Intervention $intervention,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(InterventionType::class, $intervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Nouvelle signature éventuelle
            $dataUrl = (string) $form->get('signature_draw')->getData();
            if ($dataUrl) {
                $publicPath = $this->saveSignatureFromDataUrl(
                    $dataUrl,
                    $this->signaturesDir()
                );
                $intervention->setSignaturePath($publicPath);
            }

            $em->flush();
            return $this->redirectToRoute('app_intervention_index');
        }

        return $this->render('intervention/edit.html.twig', [
            'intervention' => $intervention,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_intervention_delete', methods: ['POST'])]
    public function delete(Request $request, Intervention $intervention, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $intervention->getId(), $request->request->get('_token'))) {
            $em->remove($intervention);
            $em->flush();
        }
        return $this->redirectToRoute('app_intervention_index');
    }

    private function saveSignatureFromDataUrl(string $dataUrl, string $targetDir): string
    {
        // data:image/png;base64,xxxx
        if (!str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw new \RuntimeException('Signature invalide.');
        }
        $base64 = substr($dataUrl, 22);
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new \RuntimeException('Décodage base64 échoué.');
        }

        $fs = new Filesystem();
        if (!$fs->exists($targetDir)) {
            $fs->mkdir($targetDir, 0775);
        }

        $filename = 'sig_' . bin2hex(random_bytes(8)) . '.png';
        $path = rtrim($targetDir, '/') . '/' . $filename;
        file_put_contents($path, $binary);

        // Chemin public pour l'affichage
        return '/uploads/signatures/' . $filename;
    }

    #[Route('/{id}/print', name: 'app_intervention_print', methods: ['GET'])]
    public function print(Intervention $intervention): Response
    {
        return $this->render('intervention/print.html.twig', [
            'intervention' => $intervention,
        ]);
    }
}
