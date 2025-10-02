<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\ListAgence;
use App\Entity\ModificationRequest;
use App\Entity\Organisme;
use App\Form\ClientSuggestionType;
use App\Repository\ModificationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/modifications')]
class ModificationRequestController extends AbstractController
{
    #[Route('/client/{id}/suggest', name: 'client_suggest_edit', methods: ['GET','POST'])]
    #[Template('modification/suggest.html.twig')]
    public function suggest(
        Client $client,
        Request $request,
        EntityManagerInterface $em
    ): array|Response {
        // Pré-remplissage (valeurs actuelles + user si connecté)
        $defaults = [
            'nom'         => $client->getNom(),
            'codetls'     => $client->getCodetls(),
            'organisme'   => $client->getOrganisme(),
            'key'         => $client->getKey(),
            'agence'      => $client->getAgence(),
            'codeAlarme'  => $client->getCodeAlarme(),
            'description' => $client->getDescription(),
            'keycodeild'  => $client->getKeycodeild(),
            'adresse'     => $client->getAdresse(),
            'information' => $client->getInformation(),
            'latitude'    => $client->getLatitude(),
            'longitude'   => $client->getLongitude(),
            'submittedByName'  => $this->guessUserName(),
            'submittedByEmail' => $this->guessUserEmail(),
            'comment'          => null,
        ];

        $form = $this->createForm(ClientSuggestionType::class, $defaults);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // Si l'utilisateur laisse vide, on reprend ses infos
            $submittedByName  = trim((string)($data['submittedByName']  ?? '')) ?: $this->guessUserName();
            $submittedByEmail = trim((string)($data['submittedByEmail'] ?? '')) ?: $this->guessUserEmail();

            // --- Diff robuste ---
            // Spécification de type par champ
            $spec = [
                'nom' => 'string',
                'codetls' => 'string',
                'organisme' => ['entity', Organisme::class],
                'key' => 'string',
                'agence' => ['entity', ListAgence::class],
                'codeAlarme' => 'string',
                'description' => 'string',
                'keycodeild' => 'string',
                'adresse' => 'string',
                'information' => 'string',
                'latitude' => 'float',
                'longitude' => 'float',
            ];

            $normalize = static function ($value, string $type) {
                switch ($type) {
                    case 'string':
                        $v = trim((string)($value ?? ''));
                        return $v === '' ? null : $v;
                    case 'float':
                        if ($value === null || $value === '') return null;
                        return (float) $value;
                }
                return $value;
            };

            $entityId = static function ($v) {
                return (\is_object($v) && method_exists($v, 'getId')) ? $v->getId() : ($v ?: null);
            };

            $changes = [];

            foreach ($spec as $field => $type) {
                if (\is_array($type) && $type[0] === 'entity') {
                    $cur = $entityId($defaults[$field] ?? null);
                    $new = $entityId($data[$field] ?? null);
                    if ($cur !== $new) {
                        $changes[$field] = ['from' => $cur, 'to' => $new];
                    }
                } else {
                    $cur = $normalize($defaults[$field] ?? null, $type);
                    $new = $normalize($data[$field] ?? null, $type);
                    if ($cur !== $new) {
                        $changes[$field] = ['from' => $cur, 'to' => $new];
                    }
                }
            }

            if (!$changes) {
                $this->addFlash('info', 'Aucun changement détecté. Modifiez au moins un champ puis renvoyez la suggestion.');
                return $this->redirectToRoute('client_suggest_edit', ['id' => $client->getId()], Response::HTTP_SEE_OTHER);
            }

            $req = (new ModificationRequest())
                ->setClient($client)
                ->setChanges($changes)
                ->setSubmittedByName($submittedByName)
                ->setSubmittedByEmail($submittedByEmail)
                ->setComment($data['comment'] ?? null)
                ->setStatus(ModificationRequest::STATUS_PENDING);

            $em->persist($req);
            $em->flush();

            $this->addFlash('success', 'Merci ! Votre suggestion a été envoyée pour validation.');
            return $this->redirectToRoute('client_show', ['id' => $client->getId()], Response::HTTP_SEE_OTHER);
        }

        return ['client' => $client, 'form' => $form->createView()];
    }

    #[Route('/admin', name: 'mod_request_admin_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Template('modification/admin_index.html.twig')]
    public function adminIndex(ModificationRequestRepository $repo): array
    {
        return ['pending' => $repo->findPending()];
    }

    #[Route('/admin/{id}/review', name: 'mod_request_review', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Template('modification/review.html.twig')]
    public function review(ModificationRequest $req, Request $request, EntityManagerInterface $em): array|Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'approve') {
                $this->applyChanges($req, $em);
                $req->setStatus(ModificationRequest::STATUS_APPROVED);
            } else {
                $req->setStatus(ModificationRequest::STATUS_REJECTED);
            }

            $req->setReviewedAt(new \DateTimeImmutable());
            if (method_exists($this->getUser() ?? null, 'getId')) {
                $req->setReviewedBy($this->getUser());
            }

            $em->flush();
            $this->addFlash('success', $action === 'approve' ? 'Suggestion appliquée.' : 'Suggestion rejetée.');
            return $this->redirectToRoute('mod_request_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        $changes = $this->humanizeChanges($req->getChanges(), $em);
        $fieldLabels = [
            'nom' => 'Nom','codetls' => 'Code TLS','organisme' => 'Organisme','key' => 'Clés',
            'agence' => 'Agence','codeAlarme' => 'Code alarme','description' => 'Description',
            'keycodeild' => 'Keycode ILD','adresse' => 'Adresse','information' => 'Information',
            'latitude' => 'Latitude','longitude' => 'Longitude',
        ];

        return ['req' => $req, 'changes' => $changes, 'fieldLabels' => $fieldLabels];
    }

    private function applyChanges(ModificationRequest $req, EntityManagerInterface $em): void
    {
        $client = $req->getClient();
        $c = $req->getChanges();

        $map = [
            'nom' => fn($v) => $client->setNom($v),
            'codetls' => fn($v) => $client->setCodetls($v),
            'key' => fn($v) => $client->setKey($v),
            'codeAlarme' => fn($v) => $client->setCodeAlarme($v),
            'description' => fn($v) => $client->setDescription($v),
            'keycodeild' => fn($v) => $client->setKeycodeild($v),
            'adresse' => fn($v) => $client->setAdresse($v),
            'information' => fn($v) => $client->setInformation($v),
            'latitude' => fn($v) => $client->setLatitude($v),
            'longitude' => fn($v) => $client->setLongitude($v),
        ];
        foreach ($map as $f => $setter) {
            if (isset($c[$f])) { $setter($c[$f]['to']); }
        }

        if (isset($c['organisme'])) {
            $org = $c['organisme']['to'] ? $em->getRepository(Organisme::class)->find($c['organisme']['to']) : null;
            $client->setOrganisme($org);
        }
        if (isset($c['agence'])) {
            $ag = $c['agence']['to'] ? $em->getRepository(ListAgence::class)->find($c['agence']['to']) : null;
            $client->setAgence($ag);
        }
    }

    private function humanizeChanges(array $changes, EntityManagerInterface $em): array
    {
        $orgRepo = $em->getRepository(Organisme::class);
        $agRepo  = $em->getRepository(ListAgence::class);

        $resolve = static function ($id, $repo): ?string {
            if ($id === null || $id === '') return null;
            $e = $repo->find($id);
            return $e ? (method_exists($e,'getName') ? $e->getName() : (string)$id) : sprintf('ID %s (inconnu)', $id);
        };

        $pretty = $changes;
        if (isset($pretty['organisme'])) {
            $pretty['organisme']['from_label'] = $resolve($pretty['organisme']['from'] ?? null, $orgRepo);
            $pretty['organisme']['to_label']   = $resolve($pretty['organisme']['to'] ?? null, $orgRepo);
        }
        if (isset($pretty['agence'])) {
            $pretty['agence']['from_label'] = $resolve($pretty['agence']['from'] ?? null, $agRepo);
            $pretty['agence']['to_label']   = $resolve($pretty['agence']['to'] ?? null, $agRepo);
        }
        return $pretty;
    }

    private function guessUserName(): ?string
    {
        $u = $this->getUser();
        if (!$u) return null;
        foreach (['getFullName','getName'] as $m) if (method_exists($u,$m)) return (string)$u->{$m}();
        $first = null; $last = null;
        foreach (['getFirstName','getFirstname'] as $m) if (method_exists($u,$m)) { $first = (string)$u->{$m}(); break; }
        foreach (['getLastName','getLastname'] as $m)  if (method_exists($u,$m)) { $last  = (string)$u->{$m}(); break; }
        if ($first || $last) return trim($first.' '.$last);
        if (method_exists($u,'getUsername')) return (string)$u->getUsername();
        if (method_exists($u,'getUserIdentifier')) {
            $id = (string)$u->getUserIdentifier();
            return str_contains($id,'@') ? explode('@',$id)[0] : $id;
        }
        return null;
        }

    private function guessUserEmail(): ?string
    {
        $u = $this->getUser();
        if (!$u) return null;
        if (method_exists($u,'getEmail')) return (string)$u->getEmail();
        if (method_exists($u,'getUserIdentifier')) return (string)$u->getUserIdentifier();
        return null;
    }
}
