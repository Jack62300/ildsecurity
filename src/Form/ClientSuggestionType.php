<?php
namespace App\Form;

use App\Entity\Organisme;
use App\Entity\ListAgence;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reprend les champs de ClientType (sans photos).
 * Data = tableau associatif (pas d'entité liée directement).
 */
class ClientSuggestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('codetls', TextType::class, ['required' => false, 'label' => 'Code TLS'])
            ->add('organisme', EntityType::class, [
                'class'        => Organisme::class,
                'choice_label' => 'name',
                'placeholder'  => '— Sélectionner un organisme —',
                'label'        => 'TLS / DIRECT (organisme)',
                'required'     => false,
            ])
            ->add('key', TextType::class, ['required' => false, 'label' => 'Clés'])
            ->add('agence', EntityType::class, [
                'class'        => ListAgence::class,
                'choice_label' => 'name',
                'placeholder'  => '— Sélectionner une agence —',
                'label'        => 'Agence',
                'required'     => true,
            ])
            ->add('codeAlarme', TextType::class, ['required' => false, 'label' => 'Code alarme'])
            ->add('description', TextType::class, ['required' => false, 'label' => 'Description'])
            ->add('keycodeild', TextType::class, ['required' => false, 'label' => 'Keycode ILD'])
            ->add('adresse', TextType::class, ['required' => false, 'label' => 'Adresse'])
            ->add('information', TextType::class, ['required' => false, 'label' => 'Information'])
            ->add('latitude', NumberType::class, [
                'required' => false, 'scale' => 7, 'label' => 'Latitude', 'attr' => ['step' => 'any'],
            ])
            ->add('longitude', NumberType::class, [
                'required' => false, 'scale' => 7, 'label' => 'Longitude', 'attr' => ['step' => 'any'],
            ])
            // Infos “publique” du proposant
            ->add('submittedByName', TextType::class, [
                'required' => false, 'label' => 'Votre nom (optionnel)',
            ])
            ->add('submittedByEmail', EmailType::class, [
                'required' => false, 'label' => 'Votre email (optionnel)',
            ])
            ->add('comment', TextareaType::class, [
                'required' => true, 'label' => 'Commentaire',
                'attr' => ['rows' => 4, 'placeholder' => 'Expliquez brièvement la correction proposée…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,        // tableau
            'csrf_protection' => true,
        ]);
    }
}
