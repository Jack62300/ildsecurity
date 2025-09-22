<?php
namespace App\Fleet;

use App\Entity\FuelFillUp;
use App\Entity\Vehicle;
use App\Repository\FuelFillUpRepository;
use Doctrine\ORM\EntityManagerInterface;

class FuelService
{
    public function __construct(private EntityManagerInterface $em, private FuelFillUpRepository $repo) {}

    public function createFillUp(Vehicle $vehicle, \DateTimeImmutable $date, int $odometerKm, float $pricePerLiter, float $liters): FuelFillUp
    {
        $prev = $this->repo->findPrevForVehicle($vehicle);
        $delta = 0;
        if ($prev) {
            $delta = max(0, $odometerKm - $prev->getOdometerKm());
        }

        $fill = (new FuelFillUp())
            ->setVehicle($vehicle)
            ->setFilledAt($date)
            ->setOdometerKm($odometerKm)
            ->setPricePerLiter($pricePerLiter)
            ->setLiters($liters)
            ->setKmSinceLast($delta)
            ->setTotalPrice(round($liters * $pricePerLiter, 2));

        $this->em->persist($fill); $this->em->flush();

        return $fill;
    }
}
