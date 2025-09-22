<?php
namespace App\Form;

use App\Entity\FuelFillUp;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FuelFillUpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => fn(Vehicle $v) => $v->getPlate().' — '.($v->getModel() ?? ''),
                'placeholder' => 'Sélectionner un véhicule',
                // Optionnel : data-photo / data-plaque pour un rendu enrichi
                'choice_attr' => fn(Vehicle $v) => [
                    'data-photo'  => $v->getPhotoName() ? '/uploads/vehicles/'.$v->getPhotoName() : '',
                    'data-plaque' => $v->getPlate(),
                ],
                'label' => 'Véhicule',
            ])
            ->add('filledAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date/heure du plein',
            ])
            ->add('odometer', IntegerType::class, [
                'label' => 'Kilométrage (km)',
            ])
            ->add('pricePerLitre', NumberType::class, [
                'label' => 'Prix au litre (€)',
                'scale' => 3,
            ])
            ->add('liters', NumberType::class, [
                'label' => 'Nombre de litres',
                'scale' => 2,
            ])
            // totalPrice est mappé (on le recalculera côté serveur)
            ->add('totalPrice', MoneyType::class, [
                'label' => 'Prix total (€)',
                'currency' => 'EUR',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut Compta',
                'choices' => [
                    'En attente de traitement' => 'pending',
                    'Validé' => 'validated',
                    'Refusé' => 'rejected',
                ],
                'data' => 'pending', // Valeur par défaut
                'placeholder' => 'Sélectionner un statut',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FuelFillUp::class]);
    }
}