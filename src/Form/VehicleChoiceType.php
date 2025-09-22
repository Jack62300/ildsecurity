<?php
namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class VehicleChoiceType extends AbstractType
{
    public function getParent(): ?string { return EntityType::class; }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Vehicle::class,
            'choice_label' => fn(Vehicle $v) => sprintf('%s — %s', $v->getPlate(), $v->getModel() ?? ''),
            'choice_value' => 'id',
            'placeholder'  => 'Sélectionner un véhicule',
            // On transmet un attribut data-photo pour le rendu JS du dropdown
            'choice_attr' => function (Vehicle $v) {
                // On reconstruit l'URL publique : /uploads/vehicles/<photoName>
                $url = $v->getPhotoName() ? '/uploads/vehicles/'.$v->getPhotoName() : '';
                return ['data-photo' => $url];
            },
        ]);
    }
}
