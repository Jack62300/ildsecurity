<?php

namespace App\Controller\Admin\Fuel;

use DateTimeImmutable;
use App\Entity\Vehicle;
use App\Entity\FuelFillUp;
use App\Form\FuelFillUpType;
use App\Repository\FuelFillUpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/admin/fuel', name: 'admin_fuel_')]
class FuelAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        // Liste des véhicules (pour l'entête + groupement par véhicule dans le Twig)
        $vehicles = $em->getRepository(Vehicle::class)->findBy([], ['plate' => 'ASC']);

        // QB des pleins NON validés (status != 'validated' ou NULL), plus récents d'abord
        $baseQb = $em->getRepository(FuelFillUp::class)->createQueryBuilder('f')
            ->addSelect('v')
            ->leftJoin('f.vehicle', 'v')
            ->andWhere("COALESCE(f.status, '') <> :validated")
            ->setParameter('validated', 'validated')
            ->orderBy('f.filledAt', 'DESC');

        // === Compteur global de pleins à traiter (pour l’entête) ===
        $totalToProcess = (int) (clone $baseQb)
            ->select('COUNT(f.id)')
            ->resetDQLPart('orderBy') // inutile pour un COUNT
            ->getQuery()
            ->getSingleScalarResult();

        // === Pagination PAR VÉHICULE (10 éléments / page par défaut) ===
        $fillsByVehicle = [];
        $limitPerVehicle = 10; // <-- demandé : 10 entités

        foreach ($vehicles as $vehicle) {
            $vehicleQb = clone $baseQb;
            $vehicleQb
                ->andWhere('f.vehicle = :veh')
                ->setParameter('veh', $vehicle);

            // Paramètre de page unique pour ce véhicule
            $pageParam = 'page_' . $vehicle->getId();
            $page = max(1, (int) $request->query->get($pageParam, 1));

            $fillsByVehicle[$vehicle->getId()] = $paginator->paginate(
                $vehicleQb,
                $page,
                $limitPerVehicle,
                [
                    // Important : pour avoir une pagination indépendante par tableau
                    'pageParameterName' => $pageParam,
                ]
            );
        }

        // ===== CALCULS STATISTIQUES SUR TOUTES LES DONNÉES =====
        // (inchangé)
        $allFillsQb = $em->getRepository(FuelFillUp::class)->createQueryBuilder('f')
            ->addSelect('v')
            ->leftJoin('f.vehicle', 'v')
            ->andWhere("COALESCE(f.status, '') <> :validated")
            ->setParameter('validated', 'validated');

        $allFills = $allFillsQb->getQuery()->getResult();

        $globalStats = [
            'totalDistance' => 0,
            'pendingCount' => 0,
            'totalCost' => 0,
            'totalLiters' => 0
        ];

        foreach ($allFills as $fill) {
            if ($fill->getDistanceKm() && $fill->getDistanceKm() > 0) {
                $globalStats['totalDistance'] += $fill->getDistanceKm();
            }
            if ($fill->getStatus() === 'pending') {
                $globalStats['pendingCount']++;
            }
            $globalStats['totalCost']  += $fill->getTotalPrice();
            $globalStats['totalLiters'] += $fill->getLiters();
        }

        $vehicleStats = [];
        foreach ($vehicles as $vehicle) {
            $vehicleStats[$vehicle->getId()] = [
                'fillsCount' => 0,
                'totalDistance' => 0,
                'totalCost' => 0,
                'totalLiters' => 0
            ];
        }
        foreach ($allFills as $fill) {
            $vehicleId = $fill->getVehicle()->getId();
            if (isset($vehicleStats[$vehicleId])) {
                $vehicleStats[$vehicleId]['fillsCount']++;
                $vehicleStats[$vehicleId]['totalCost']   += $fill->getTotalPrice();
                $vehicleStats[$vehicleId]['totalLiters'] += $fill->getLiters();
                if ($fill->getDistanceKm() && $fill->getDistanceKm() > 0) {
                    $vehicleStats[$vehicleId]['totalDistance'] += $fill->getDistanceKm();
                }
            }
        }

        return $this->render('admin/fuel/index.html.twig', [
            'vehicles'        => $vehicles,
            'fillsByVehicle'  => $fillsByVehicle,     // <--- chaque valeur est un objet KnpPaginator
            'totalToProcess'  => $totalToProcess,     // <--- pour l’entête
            'globalStats'     => $globalStats,
            'vehicleStats'    => $vehicleStats,
        ]);
    }


    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $req,
        EntityManagerInterface $em,
        FuelFillUpRepository $repo
    ): Response {
        $fill = new FuelFillUp();
        $form = $this->createForm(FuelFillUpType::class, $fill);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sécurité: recalcul serveur (total)
            $total = (float)$fill->getLiters() * (float)$fill->getPricePerLitre();
            $fill->setTotalPrice(number_format($total, 2, '.', ''));

            // === CALCUL DE DISTANCE CORRIGÉ ===
            if ($fill->getVehicle()) {
                // Cherche le plein avec l'odomètre le plus proche ET inférieur
                $prev = $repo->findPreviousOdometerForVehicle(
                    $fill->getVehicle(),
                    $fill->getOdometer()
                );

                if ($prev && $fill->getOdometer() > $prev->getOdometer()) {
                    $distance = $fill->getOdometer() - $prev->getOdometer();
                    $fill->setDistanceKm($distance);
                    $fill->setStatus('pending'); // Valeur par défaut

                    $this->addFlash('info', sprintf(
                        "Distance calculée : %d km (de %d à %d km)",
                        $distance,
                        $prev->getOdometer(),
                        $fill->getOdometer()
                    ));
                } else {
                    $fill->setDistanceKm(null);
                    if ($prev) {
                        $this->addFlash('warning', sprintf(
                            "Odomètre (%d km) inférieur ou égal au précédent (%d km). Distance non calculée.",
                            $fill->getOdometer(),
                            $prev->getOdometer()
                        ));
                    } else {
                        $this->addFlash('info', 'Premier plein pour ce véhicule. Aucune distance calculée.');
                    }
                }
            }

            $em->persist($fill);
            $em->flush();

            $this->addFlash('success', 'Plein enregistré.');
            return $this->redirectToRoute('admin_fuel_index');
        }

        return $this->render('admin/fuel/form.html.twig', [
            'form' => $form->createView(),
            'fill' => $fill,
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        FuelFillUp $fill,
        Request $req,
        EntityManagerInterface $em,
        FuelFillUpRepository $repo
    ): Response {
        $form = $this->createForm(FuelFillUpType::class, $fill);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcul serveur
            $total = (float)$fill->getLiters() * (float)$fill->getPricePerLitre();
            $fill->setTotalPrice(number_format($total, 2, '.', ''));

            $prev = $fill->getVehicle() ? $repo->findPreviousForVehicle($fill->getVehicle(), $fill->getFilledAt()) : null;
            if ($prev && $prev->getId() !== $fill->getId() && $fill->getOdometer() >= $prev->getOdometer()) {
                $fill->setDistanceKm($fill->getOdometer() - $prev->getOdometer());
            } else {
                $fill->setDistanceKm(null);
            }

            $em->flush();
            $this->addFlash('success', 'Plein mis à jour.');
            return $this->redirectToRoute('admin_fuel_index');
        }

        return $this->render('admin/fuel/form.html.twig', [
            'form' => $form->createView(),
            'fill' => $fill,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(FuelFillUp $fill): Response
    {
        return $this->render('admin/fuel/show.html.twig', [
            'fill' => $fill,
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(FuelFillUp $fill, Request $req, EntityManagerInterface $em): Response
    {
        $token = (string)$req->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_fill_' . $fill->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_fuel_index');
        }

        $em->remove($fill);
        $em->flush();
        $this->addFlash('success', 'Plein supprimé.');
        return $this->redirectToRoute('admin_fuel_index');
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/fuel/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(FuelFillUp $fill, EntityManagerInterface $em): Response
    {
        $fill->setStatus('validated');
        $em->flush();

        $this->addFlash('success', 'Plein validé avec succès.');
        return $this->redirectToRoute('admin_fuel_index');
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/fuel/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(FuelFillUp $fill, EntityManagerInterface $em): Response
    {
        $fill->setStatus('rejected');
        $em->flush();

        $this->addFlash('warning', 'Plein refusé.');
        return $this->redirectToRoute('admin_fuel_index');
    }


    #[Route('/export', name: 'export', methods: ['GET'], priority: 10)]
    public function export(Request $request, EntityManagerInterface $em): StreamedResponse
    {
        $status     = $request->query->get('status');                 // ex: validated, pending...
        $dateFrom   = $request->query->get('date_from');              // yyyy-mm-dd
        $dateTo     = $request->query->get('date_to');                // yyyy-mm-dd
        $vehicleIds = (array) $request->query->all('vehicles');       // array d’ids (vehicles[])

        $qb = $em->getRepository(FuelFillUp::class)->createQueryBuilder('f')
            ->addSelect('v')
            ->leftJoin('f.vehicle', 'v')
            ->orderBy('f.filledAt', 'ASC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('f.status = :status')->setParameter('status', $status);
        }
        if ($dateFrom) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateFrom . ' 00:00:00')
                ?: new \DateTimeImmutable($dateFrom . ' 00:00:00');
            $qb->andWhere('f.filledAt >= :from')->setParameter('from', $from);
        }
        if ($dateTo) {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTo . ' 23:59:59')
                ?: new \DateTimeImmutable($dateTo . ' 23:59:59');
            $qb->andWhere('f.filledAt <= :to')->setParameter('to', $to);
        }
        if (!empty($vehicleIds)) {
            $qb->andWhere('v.id IN (:vids)')->setParameter('vids', $vehicleIds);
        }

        $response = new StreamedResponse(function () use ($qb) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 (Excel friendly)
            fwrite($out, "\xEF\xBB\xBF");

            // En-têtes CSV
            fputcsv($out, [
                'ID',
                'Véhicule (plaque)',
                'Date',
                'Heure',
                'Odomètre (km)',
                'Litres',
                'Prix/L (€)',
                'Total (€)',
                'Distance (km)',
                'Statut',
            ], ';');

            // Parcours streamé
            foreach ($qb->getQuery()->toIterable([], AbstractQuery::HYDRATE_OBJECT) as $fill) {
                /** @var FuelFillUp $fill */
                $vehicle = $fill->getVehicle();

                fputcsv($out, [
                    $fill->getId(),
                    $vehicle ? $vehicle->getPlate() : '',
                    $fill->getFilledAt() ? $fill->getFilledAt()->format('d/m/Y') : '',
                    $fill->getFilledAt() ? $fill->getFilledAt()->format('H:i') : '',
                    $fill->getOdometer() !== null ? number_format((float)$fill->getOdometer(), 0, ',', ' ') : '',
                    $fill->getLiters() !== null ? number_format((float)$fill->getLiters(), 2, ',', ' ') : '',
                    $fill->getPricePerLitre() !== null ? number_format((float)$fill->getPricePerLitre(), 3, ',', ' ') : '',
                    $fill->getTotalPrice() !== null ? number_format((float)$fill->getTotalPrice(), 2, ',', ' ') : '',
                    $fill->getDistanceKm() !== null ? number_format((float)$fill->getDistanceKm(), 0, ',', ' ') : '',
                    $fill->getStatus() ?? '',
                ], ';');
            }

            fclose($out);
        });

        $filename = 'export_fuel_' . (new \DateTime())->format('Ymd_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
