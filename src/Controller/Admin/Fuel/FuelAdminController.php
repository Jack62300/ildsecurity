<?php

namespace App\Controller\Admin\Fuel;

use App\Entity\Vehicle;
use App\Entity\FuelFillUp;
use App\Form\FuelFillUpType;
use App\Repository\FuelFillUpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/fuel', name: 'admin_fuel_')]
class FuelAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        // Liste des véhicules (pour l'entête + groupement par véhicule dans ton Twig)
        $vehicles = $em->getRepository(Vehicle::class)->findBy([], ['plate' => 'ASC']);

        // QB des pleins NON validés (status != 'validated' ou NULL), plus récent d'abord
        $qb = $em->getRepository(FuelFillUp::class)->createQueryBuilder('f')
            ->addSelect('v')
            ->leftJoin('f.vehicle', 'v')
            ->andWhere("COALESCE(f.status, '') <> :validated")
            ->setParameter('validated', 'validated')
            ->orderBy('f.filledAt', 'DESC');

        // Pagination (?page=1&limit=20)
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, (int) $request->query->get('limit', 20));

        $fills = $paginator->paginate($qb, $page, $limit);

        // ===== CALCULS STATISTIQUES SUR TOUTES LES DONNÉES =====
        // Récupération de TOUS les pleins non validés pour les statistiques
        $allFillsQb = $em->getRepository(FuelFillUp::class)->createQueryBuilder('f')
            ->addSelect('v')
            ->leftJoin('f.vehicle', 'v')
            ->andWhere("COALESCE(f.status, '') <> :validated")
            ->setParameter('validated', 'validated');

        $allFills = $allFillsQb->getQuery()->getResult();

        // Calculs statistiques globaux
        $globalStats = [
            'totalDistance' => 0,
            'pendingCount' => 0,
            'totalCost' => 0,
            'totalLiters' => 0
        ];

        foreach ($allFills as $fill) {
            // Distance
            if ($fill->getDistanceKm() && $fill->getDistanceKm() > 0) {
                $globalStats['totalDistance'] += $fill->getDistanceKm();
            }

            // Statut pending
            if ($fill->getStatus() === 'pending') {
                $globalStats['pendingCount']++;
            }

            // Coût et litres
            $globalStats['totalCost'] += $fill->getTotalPrice();
            $globalStats['totalLiters'] += $fill->getLiters();
        }

        // Calculs statistiques par véhicule
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
                $vehicleStats[$vehicleId]['totalCost'] += $fill->getTotalPrice();
                $vehicleStats[$vehicleId]['totalLiters'] += $fill->getLiters();

                if ($fill->getDistanceKm() && $fill->getDistanceKm() > 0) {
                    $vehicleStats[$vehicleId]['totalDistance'] += $fill->getDistanceKm();
                }
            }
        }

        return $this->render('admin/fuel/index.html.twig', [
            'vehicles' => $vehicles,
            'fills' => $fills, // objet KnpPaginator (getTotalItemCount / haveToPaginate OK)
            'globalStats' => $globalStats,
            'vehicleStats' => $vehicleStats,
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
            // Sécurité: recalcul serveur (total & distance)
            $total = (float)$fill->getLiters() * (float)$fill->getPricePerLitre();
            $fill->setTotalPrice(number_format($total, 2, '.', ''));

            $prev = $fill->getVehicle() ? $repo->findPreviousForVehicle($fill->getVehicle(), $fill->getFilledAt()) : null;
            if ($prev && $fill->getOdometer() >= $prev->getOdometer()) {
                $fill->setDistanceKm($fill->getOdometer() - $prev->getOdometer());
            } else {
                $fill->setDistanceKm(null);
                if ($prev) {
                    $this->addFlash('warning', sprintf(
                        "Le kilométrage (%d) est inférieur au précédent plein (%d). Distance non calculée.",
                        $fill->getOdometer(),
                        $prev->getOdometer()
                    ));
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

    #[Route('/admin/fuel/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(FuelFillUp $fill, EntityManagerInterface $em): Response
    {
        $fill->setStatus('validated');
        $em->flush();

        $this->addFlash('success', 'Plein validé avec succès.');
        return $this->redirectToRoute('admin_fuel_index');
    }

    #[Route('/admin/fuel/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(FuelFillUp $fill, EntityManagerInterface $em): Response
    {
        $fill->setStatus('rejected');
        $em->flush();

        $this->addFlash('warning', 'Plein refusé.');
        return $this->redirectToRoute('admin_fuel_index');
    }
}
