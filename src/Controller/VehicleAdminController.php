<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/vehicles')]
class VehicleAdminController extends AbstractController
{
    #[Route('', name: 'admin_vehicle_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $vehicles = $em->getRepository(Vehicle::class)->findBy([], ['plate' => 'ASC']);
        return $this->render('admin/vehicles/vehicle_index.html.twig', compact('vehicles'));
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'admin_vehicle_new', methods: ['GET', 'POST'])]
    public function new(Request $req, EntityManagerInterface $em): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule créé');
            return $this->redirectToRoute('admin_vehicle_index');
        }

        return $this->render('admin/vehicles/vehicle_form.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'admin_vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(Vehicle $vehicle, Request $req, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Véhicule mis à jour');
            return $this->redirectToRoute('admin_vehicle_index');
        }

        return $this->render('admin/vehicles/vehicle_form.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'admin_vehicle_delete', methods: ['POST'])]
    public function delete(Vehicle $vehicle, Request $req, EntityManagerInterface $em): Response
    {
        $token = (string) $req->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_vehicle_' . $vehicle->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_vehicle_index');
        }

        $em->remove($vehicle);
        $em->flush();

        $this->addFlash('success', 'Véhicule supprimé');
        return $this->redirectToRoute('admin_vehicle_index');
    }
}
