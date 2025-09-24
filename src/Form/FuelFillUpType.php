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
                'choice_attr' => fn(Vehicle $v) => [
                    'data-photo'  => $v->getPhotoName() ? '/uploads/vehicles/'.$v->getPhotoName() : '',
                    'data-plaque' => $v->getPlate(),
                ],
                'label' => 'Véhicule',
            ])
            ->add('filledAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date/heure du plein',
                'attr' => [
                    'class' => 'form-control',
                    // Optimisation mobile pour date/heure
                    'inputmode' => 'none', // Évite l'affichage du clavier pour les datetime-local
                ],
            ])
            ->add('odometer', IntegerType::class, [
                'label' => 'Kilométrage (km)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 15420',
                    // Clavier numérique pour les entiers
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => 0,
                    'max' => 999999,
                ],
            ])
            ->add('pricePerLitre', NumberType::class, [
                'label' => 'Prix au litre (€)',
                'scale' => 3,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 1.449',
                    'step' => '0.001',
                    // Clavier numérique avec décimales
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]*[.,]?[0-9]*',
                    'min' => 0,
                    'max' => 10,
                ],
            ])
            ->add('liters', NumberType::class, [
                'label' => 'Nombre de litres',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 45.25',
                    'step' => '0.01',
                    // Clavier numérique avec décimales
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]*[.,]?[0-9]*',
                    'min' => 0,
                    'max' => 200,
                ],
            ])
            ->add('totalPrice', MoneyType::class, [
                'label' => 'Prix total (€)',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'total-field',
                    'placeholder' => 'Calculé automatiquement',
                    'step' => '0.01',
                    // Clavier numérique avec décimales pour les montants
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]*[.,]?[0-9]*',
                    'min' => 0,
                    'max' => 500,
                ],
            ])
            //  ->add('status', ChoiceType::class, [
            //     'label' => 'Statut Compta',
            //     'choices' => [
            //         'En attente de traitement' => 'pending',
            //         'Validé' => 'validated',
            //         'Refusé' => 'rejected',
            //     ],
            //     'data' => 'pending',
            //     'placeholder' => 'Sélectionner un statut',
            //     'attr' => [
            //         'class' => 'form-select'
            //     ],
            // ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes additionnelles (station, type de carburant, etc.)',
                    // Amélioration mobile pour textarea
                    'autocapitalize' => 'sentences',
                    'autocomplete' => 'off',
                    'spellcheck' => 'true',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FuelFillUp::class]);
    }
}