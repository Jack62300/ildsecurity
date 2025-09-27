<?php

namespace App\Form;

use App\Entity\Intervention;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class InterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('client', TextType::class, ['required' => true])
            ->add('adresse', TextType::class, ['required' => true])
            ->add('ville', TextType::class, ['required' => true])

            // VEILLE (1 ligne = 1 case)
            ->add('vIntrusion', CheckboxType::class, ['required' => false, 'label' => 'Intrusion'])
            ->add('vIncendie', CheckboxType::class, ['required' => false, 'label' => 'Incendie'])
            ->add('vAgression', CheckboxType::class, ['required' => false, 'label' => 'Agression'])
            ->add('vDefautSecteur', CheckboxType::class, ['required' => false, 'label' => 'Défaut secteur'])
            ->add('vDefautBatterie', CheckboxType::class, ['required' => false, 'label' => 'Défaut batterie'])
            ->add('vAbsTest', CheckboxType::class, ['required' => false, 'label' => 'ABS test'])
            ->add('vAbsMes', CheckboxType::class, ['required' => false, 'label' => 'ABS MES'])
            ->add('vMhsNonAutorisee', CheckboxType::class, ['required' => false, 'label' => 'MHS non autorisée'])
            ->add('vMaintenance', CheckboxType::class, ['required' => false, 'label' => 'Maintenance'])
            ->add('vTechnique', CheckboxType::class, ['required' => false, 'label' => 'Technique'])
            ->add('vAscenseur', CheckboxType::class, ['required' => false, 'label' => 'Ascenseur'])
            ->add('vAutre', CheckboxType::class, ['required' => false, 'label' => 'Autre'])

            // Compte rendu (radio)
            ->add('compteRendu', ChoiceType::class, [
                'choices' => [
                    'Intervention' => 'intervention',
                    'Ronde' => 'ronde',
                    'Gardiennage' => 'gardiennage',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('avecMoyenAcces', ChoiceType::class, [
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
                'multiple' => false,
                'label' => "Avec moyen d'accès",
            ])

            ->add('dateBon', DateType::class, ['widget' => 'single_text', 'required' => false])

            ->add('heureAppel', DateTimeType::class, ['widget' => 'single_text', 'required' => false])
            ->add('heureArrivee', DateTimeType::class, ['widget' => 'single_text', 'required' => false])
            ->add('heureDepart', DateTimeType::class, ['widget' => 'single_text', 'required' => false])

            // ------- DÉTAIL (1 ligne = 1 choix) -------
            ->add('constatMeteo', ChoiceType::class, [
                'label' => 'Constat météo',
                'choices' => [
                    'Vent fort' => 'vent_fort',
                    'Pluie' => 'pluie',
                    'Orage' => 'orage',
                    'Brouillard' => 'brouillard',
                    'Neige' => 'neige',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            ->add('circulation', ChoiceType::class, [
                'label' => 'Circulation',
                'choices' => ['Bonne' => 'bonne', 'Mauvaise' => 'mauvaise'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('circulationMotif', TextType::class, ['required' => false, 'label' => 'Motif (si mauvaise)'])

           ->add('circuitVerification', ChoiceType::class, [
                'label' => 'Circuit de vérification',
                'choices' => [
                    'Intérieur' => 'interieur',
                    'Extérieur' => 'exterieur',
                ],
                'expanded' => true,  // cases à cocher
                'multiple' => true,  // tableau en sortie
                'required' => false,
            ])

            ->add('presence', ChoiceType::class, [
                'label' => 'Présence',
                'choices' => [
                    'Client'       => 'client',
                    'Police'       => 'police',
                    'Gendarmerie'  => 'gendarmerie',
                    'Pompiers'     => 'pompiers',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('circuitPoints', TextType::class, ['required' => false, 'label' => 'Préciser les points (si extérieur)'])

            ->add('lumiereAllumee', ChoiceType::class, [
                'label' => 'Lumière allumée',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('lumierePiece', TextType::class, ['required' => false, 'label' => 'Pièce (si oui)'])

            ->add('issuesOuvertes', ChoiceType::class, [
                'label' => 'Issue(s) ouverte(s)',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('issuesLesquelles', TextType::class, ['required' => false, 'label' => 'Lesquelles (si oui)'])

            ->add('sireneEnFonction', ChoiceType::class, [
                'label' => 'Sirène en fonction',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            ->add('systemeEtat', ChoiceType::class, [
                'label' => 'Système',
                'choices' => ['En service' => 'en_service', 'Hors service' => 'hors_service'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            ->add('remiseEnService', ChoiceType::class, [
                'label' => 'Remise en service du système',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            ->add('zones', ChoiceType::class, [
                'label' => 'Zones',
                'choices' => ['Zone(s) en anomalies' => 'anomalies', 'Zones isolées' => 'zones_isolees'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            ->add('effraction', ChoiceType::class, [
                'label' => 'Effraction constatée',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])

            

            ->add('miseEnPlace', ChoiceType::class, [
                'label' => 'Mise en place de',
                'choices' => ['ADS' => 'ads', 'Maître chien' => 'maitre_chien'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('demandePar', TextType::class, ['required' => false, 'label' => 'Demandé par'])

            ->add('personnelSurPlace', ChoiceType::class, [
                'label' => 'Personnel sur place',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('personnelNote', TextType::class, ['required' => false, 'label' => 'Précisions (si oui)'])

            ->add('vehiculeSurPlace', ChoiceType::class, [
                'label' => 'Véhicule sur place',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('vehiculeMarque', TextType::class, ['required' => false, 'label' => 'Marque (si oui)'])
            ->add('vehiculeNumero', TextType::class, ['required' => false, 'label' => 'N° (si oui)'])

            ->add('animaux', ChoiceType::class, [
                'label' => 'Présence d’animaux',
                'choices' => ['Non' => 'non', 'Oui' => 'oui'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
            ->add('animauxEspece', TextType::class, ['required' => false, 'label' => 'Espèce (si oui)'])

            ->add('commentaires', TextareaType::class, ['required' => false])

             ->add('bonDepose', ChoiceType::class, [
                'label' => "Bon d'intervention déposé",
                'choices' => [
                    'Boîte à lettres' => 'boite_lettres',
                    'Bureau' => 'bureau',
                    'Autre (préciser)' => 'autre',
                ],
                'expanded' => true, 'multiple' => false, 'required' => false,
            ])

            ->add('bonNumero', TextType::class, [
                'mapped' => false,            // ne remplit pas l'entité
                'disabled' => true,           // lecture seule
                'required' => false,
                'label' => 'Bon n°',
                'data' => $options['provisional_bon'],  // valeur calculée côté contrôleur
            ])
            ->add('bonDeposePrecision', TextType::class, ['required' => false, 'label' => 'Précision (si autre)'])

            ->add('intervenant', TextType::class, ['required' => false])
            ->add('entreprise', TextType::class, ['required' => false])
            ->add('signature_draw', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
            'provisional_bon' => null,   // notre option personnalisée
        ]);
        $resolver->setAllowedTypes('provisional_bon', ['null', 'string']);
    }
}
