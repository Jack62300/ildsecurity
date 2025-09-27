<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\ClientPhoto;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClientController extends AbstractController
{
    #[Route('/clients', name: 'clients_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, ClientRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('c')->orderBy('c.id', 'DESC');
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, (int) $request->query->get('perPage', 24));
        $pagination = $paginator->paginate($qb, $page, $perPage);

        return $this->render('index/index.html.twig', [
            'clients'    => $pagination->getItems(),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/clients/search', name: 'clients_search', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function search(Request $request, ClientRepository $repo): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $letter = (string) $request->query->get('letter', '');

        if ($q === '' && $letter === '') {
            return $this->render('clients/_cards.html.twig', ['clients' => []]);
        }

        $limit   = min((int) $request->query->get('limit', 48), 200);
        $clients = $repo->searchClients($q, $letter, $limit);

        return $this->render('clients/_cards.html.twig', ['clients' => $clients]);
    }

    #[Route('/client/{id}', name: 'client_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Client $client): Response
    {
        return $this->render('clients/show.html.twig', ['client' => $client]);
    }

    private function isMobile(Request $request): bool
    {
        $ua = $request->headers->get('User-Agent', '');
        return (bool) preg_match('/Android|iPhone|iPad|iPod|Mobile|IEMobile|WPDesktop/i', $ua);
    }

    // ===== Création : ADMIN/DEV uniquement =====
    #[Route('/clients/new', name: 'clients_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $client   = new Client();
        $isMobile = $this->isMobile($request);

        // Desktop : montrer au moins une ligne de photo si vide
        if (!$isMobile && $request->isMethod('GET') && $client->getPhotos()->count() === 0) {
            $client->addPhoto(new ClientPhoto());
        }

        $form = $this->createForm(ClientType::class, $client, [
            'is_mobile'   => $isMobile,
            'photos_only' => false, // formulaire complet
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mobile : fichiers non mappés -> créer les entités photo
            if ($isMobile && $form->has('mobileUploads')) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $files */
                $files = $form->get('mobileUploads')->getData() ?? [];
                foreach ($files as $file) {
                    if (!$file) continue;
                    $photo = new ClientPhoto();
                    $photo->setImageFile($file);
                    $client->addPhoto($photo);
                    $em->persist($photo);
                }
            }

            // Nettoyer les entrées vides
            foreach ($client->getPhotos() as $p) {
                if ($p->getImageFile() === null && !$p->getImageName()) {
                    $client->removePhoto($p);
                }
            }

            $em->persist($client);
            $em->flush();

            $this->addFlash('success', 'Client créé.');
            return $this->redirectToRoute('clients_index');
        }

        return $this->render('clients/form.html.twig', [
            'form'        => $form->createView(),
            'client'      => $client,
            'mode'        => 'new',
            'photos_only' => false,
        ]);
    }

    // ===== Édition : tout le monde (ROLE_USER) ; ADMIN = tout, USER = photos uniquement =====
    #[Route('/clients/{id}/edit', name: 'clients_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $isMobile   = $this->isMobile($request);
        $isAdmin    = $this->isGranted('ROLE_ADMIN'); // DEV hérite d'ADMIN
        $photosOnly = !$isAdmin;

        // Desktop : si aucune photo, ajouter une ligne au premier affichage
        if (!$isMobile && $request->isMethod('GET') && $client->getPhotos()->count() === 0) {
            $client->addPhoto(new ClientPhoto());
        }

        $form = $this->createForm(ClientType::class, $client, [
            'is_mobile'   => $isMobile,
            'photos_only' => $photosOnly,
        ]);
        $form->handleRequest($request);

        // Garde serveur : si pas admin -> ne laisser passer que les modifs de "photos"
        if (!$isAdmin && $form->isSubmitted()) {
            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();
            $changes = $uow->getEntityChangeSet($client) ?? [];

            foreach ($changes as $field => $change) {
                if ($field === 'photos') continue;             // autorisé
                $oldValue = $change[0] ?? null;                // on restaure l'ancienne valeur
                $setter   = 'set' . ucfirst($field);
                if (method_exists($client, $setter)) {
                    $client->$setter($oldValue);
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Mobile : ajout de photos (champ non mappé)
            if ($isMobile && $form->has('mobileUploads')) {
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $files */
                $files = $form->get('mobileUploads')->getData() ?? [];
                foreach ($files as $file) {
                    if (!$file) continue;
                    $photo = new ClientPhoto();
                    $photo->setImageFile($file);
                    $client->addPhoto($photo);
                    $em->persist($photo);
                }
            }

            // Nettoyer les items vides
            foreach ($client->getPhotos() as $p) {
                if ($p->getImageFile() === null && !$p->getImageName()) {
                    $client->removePhoto($p);
                }
            }

            $em->flush();
            $this->addFlash('success', $isAdmin ? 'Client mis à jour.' : 'Photos mises à jour.');
            return $this->redirectToRoute('clients_index');
        }

        return $this->render('clients/form.html.twig', [
            'form'        => $form->createView(),
            'client'      => $client,
            'mode'        => 'edit',
            'photos_only' => $photosOnly, // pour la vue
        ]);
    }

    // ===== Suppression du client : ADMIN/DEV =====
    #[Route('/clients/{id}/delete', name: 'clients_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_client_' . $client->getId(), (string) $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé.');
        }
        return $this->redirectToRoute('clients_index');
    }

    // ===== Suppression d'une photo : USER et plus (puisque l'utilisateur peut gérer les photos) =====
    #[Route('/clients/{id}/photo/{photoId}/remove', name: 'clients_photo_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function removePhoto(Request $request, Client $client, int $photoId, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('remove_photo_' . $client->getId() . '_' . $photoId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token invalide');
        }

        foreach ($client->getPhotos() as $photo) {
            if ($photo->getId() === $photoId) {
                $client->removePhoto($photo);
                break;
            }
        }

        $em->flush();
        $this->addFlash('success', 'Photo supprimée.');
        return $this->redirectToRoute('clients_edit', ['id' => $client->getId()]);
    }

    // ===== Export (lecture seule) =====
    #[Route('/clients/export', name: 'clients_export', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function export(Request $request, ClientRepository $repo, LoggerInterface $logger): StreamedResponse
    {
        $q         = trim((string) $request->query->get('q', ''));
        $orgId     = $request->query->get('org');
        $clientIds = (array) $request->query->all('clients');

        $qb = $repo->createQueryBuilder('c')
            ->leftJoin('c.organisme', 'o')->addSelect('o')
            ->leftJoin('c.agence', 'a')->addSelect('a')
            ->orderBy('c.nom', 'ASC');

        if ($q !== '') {
            $qb->andWhere('
                LOWER(c.nom) LIKE :q OR LOWER(c.adresse) LIKE :q OR LOWER(c.codetls) LIKE :q OR
                LOWER(c.codeAlarme) LIKE :q OR LOWER(c.key) LIKE :q OR LOWER(c.keycodeild) LIKE :q OR
                LOWER(o.name) LIKE :q
            ')->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($orgId !== null && $orgId !== '') {
            $qb->andWhere('o.id = :org')->setParameter('org', (int) $orgId);
        }

        if (!empty($clientIds)) {
            $qb->andWhere('c.id IN (:ids)')->setParameter('ids', array_map('intval', $clientIds));
        }

        $response = new StreamedResponse(function () use ($qb, $logger) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
            $out = fopen('php://output', 'w');

            try {
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, [
                    'ID','Nom','Organisme','Agence','Adresse','Code TLS','Client Key',
                    'Code Alarme','Key ILD','Latitude','Longitude','Description','Information',
                ], ';');

                foreach ($qb->getQuery()->toIterable() as $client) {
                    /** @var \App\Entity\Client $client */
                    $org = $client->getOrganisme();
                    $orgName = $org ? (method_exists($org, 'getName') ? (string) $org->getName() : ((method_exists($org, 'getNom') ? (string) $org->getName() : ''))) : '';

                    $agence = $client->getAgence();
                    $agenceName = '';
                    if ($agence) {
                        if (method_exists($agence, 'getName')) {
                            $agenceName = (string) $agence->getName();
                        } elseif (method_exists($agence, '__toString')) {
                            $agenceName = (string) $agence;
                        } else {
                            $agenceName = (string) $agence->getId();
                        }
                    }

                    fputcsv($out, [
                        $client->getId(),
                        (string) ($client->getNom() ?? ''),
                        $orgName,
                        $agenceName,
                        (string) ($client->getAdresse() ?? ''),
                        (string) ($client->getCodetls() ?? ''),
                        (string) ($client->getKey() ?? ''),
                        (string) ($client->getCodeAlarme() ?? ''),
                        (string) ($client->getKeycodeild() ?? ''),
                        $client->getLatitude() !== null ? (string) $client->getLatitude() : '',
                        $client->getLongitude() !== null ? (string) $client->getLongitude() : '',
                        (string) ($client->getDescription() ?? ''),
                        (string) ($client->getInformation() ?? ''),
                    ], ';');
                }
            } catch (\Throwable $e) {
                $logger->error('Export clients échoué: ' . $e->getMessage(), ['exception' => $e]);
                fwrite($out, "Erreur lors de l'export.\n");
            } finally {
                fclose($out);
            }
        });

        $filename = 'clients_export_' . (new \DateTime())->format('Ymd_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
