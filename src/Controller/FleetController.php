<?php
namespace App\Controller;

use App\Entity\FuelFillUp;
use App\Entity\Vehicle;
use App\Fleet\FuelService;
use App\Form\FuelFillUpType;
use App\Module\RequiresModule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[RequiresModule('fleet')]
#[IsGranted('ROLE_USER')]
#[Route('/fleet')]
class FleetController extends AbstractController
{
    #[Route('', name:'fleet_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $fills = $em->getRepository(FuelFillUp::class)->findBy([], ['filledAt'=>'DESC']);
        $vehicles = $em->getRepository(Vehicle::class)->findBy([], ['plate'=>'ASC']);
        return $this->render('fleet/index.html.twig', compact('fills','vehicles'));
    }

    #[Route('/fill/new', name:'fleet_fill_new')]
    public function new(Request $req, FuelService $svc): Response
    {
        $data = new FuelFillUp();
        $form = $this->createForm(FuelFillUpType::class, $data);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var FuelFillUp $d */
            $d = $form->getData();
            $svc->createFillUp(
                $d->getVehicle(),
                $d->getFilledAt() ?? new \DateTimeImmutable(),
                $d->getOdometerKm(),
                $d->getPricePerLiter(),
                $d->getLiters()
            );
            $this->addFlash('success', 'Plein enregistré.');
            return $this->redirectToRoute('fleet_index');
        }
        return $this->render('fleet/fill_form.html.twig', ['form' => $form->createView()]);
    }
}
