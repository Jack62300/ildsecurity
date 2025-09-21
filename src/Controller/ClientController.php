<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\ClientPhoto;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
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

    #[Route('/clients/new', name: 'clients_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $client   = new Client();
        $isMobile = $this->isMobile($request);

        // Desktop : afficher au moins 1 input file
        if (!$isMobile && $request->isMethod('GET') && $client->getPhotos()->count() === 0) {
            $client->addPhoto(new ClientPhoto());
        }

        $form = $this->createForm(ClientType::class, $client, [
            'is_mobile' => $isMobile,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // MOBILE : fichiers non mappés -> créer & persister les entités
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

            // Desktop & Mobile : nettoyer les items vides
            foreach ($client->getPhotos() as $p) {
                if ($p->getImageFile() === null && !$p->getImageName()) {
                    $client->removePhoto($p);
                }
            }

            $em->persist($client); // cascade sur photos OK
            $em->flush();

            $this->addFlash('success', 'Client créé.');
            return $this->redirectToRoute('clients_index');
        }

        return $this->render('clients/form.html.twig', [
            'form'   => $form->createView(),
            'client' => $client,
            'mode'   => 'new',
            'google_api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? '',
        ]);
    }

    #[Route('/clients/{id}/edit', name: 'clients_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $isMobile = $this->isMobile($request);

        // Desktop : si aucune photo -> un champ par défaut
        if (!$isMobile && $request->isMethod('GET') && $client->getPhotos()->count() === 0) {
            $client->addPhoto(new ClientPhoto());
        }

        $form = $this->createForm(ClientType::class, $client, [
            'is_mobile' => $isMobile,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isMobile && $form->has('mobileUploads')) {
                $files = $form->get('mobileUploads')->getData() ?? [];
                foreach ($files as $file) {
                    if (!$file) continue;
                    $photo = new ClientPhoto();
                    $photo->setImageFile($file);
                    $client->addPhoto($photo);
                    $em->persist($photo);
                }
            }

            foreach ($client->getPhotos() as $p) {
                if ($p->getImageFile() === null && !$p->getImageName()) {
                    $client->removePhoto($p);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Client mis à jour.');
            return $this->redirectToRoute('clients_index');
        }

        return $this->render('clients/form.html.twig', [
            'form'   => $form->createView(),
            'client' => $client,
            'mode'   => 'edit',
            'google_api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? '',
        ]);
    }

    #[Route('/clients/{id}/delete', name: 'clients_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_client_'.$client->getId(), (string) $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé.');
        }
        return $this->redirectToRoute('clients_index');
    }

    #[Route('/clients/{id}/photo/{photoId}/remove', name: 'clients_photo_remove', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removePhoto(Request $request, Client $client, int $photoId, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('remove_photo_'.$client->getId().'_'.$photoId, (string) $request->request->get('_token'))) {
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
}
