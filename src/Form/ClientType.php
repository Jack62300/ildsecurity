<?php
// src/Form/ClientType.php
namespace App\Form;

use App\Entity\Client;
use App\Entity\Organisme;
use App\Entity\ListAgence;
use App\Form\ClientPhotoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('codetls', null, ['required' => false, 'label' => 'Code TLS'])

            // ✅ nouvelle liste déroulante
            ->add('organisme', EntityType::class, [
                'class'        => Organisme::class,
                'choice_label' => 'name',
                'placeholder'  => '— Sélectionner un organisme —',
                'label'        => 'TLS / DIRECT (organisme)',
                'required'     => false,
            ])

            ->add('key', null, ['required' => false, 'label' => 'Clés'])
            ->add('agence', EntityType::class, [
                'class'        => ListAgence::class,
                'choice_label' => 'name',
                'placeholder'  => '— Sélectionner une agence —',
                'label'        => 'Agence',
                'required'     => true,
            ])
            ->add('codeAlarme', null, ['required' => false, 'label' => 'Code alarme'])
            ->add('description', null, ['required' => false])
            ->add('keycodeild', null, ['required' => false, 'label' => 'Keycode ILD'])
            ->add('adresse', null, ['label' => 'Adresse'])
            ->add('information', null, ['required' => false, 'label' => 'Information'])
             ->add('latitude', NumberType::class, [
                    'required' => false,
                    'scale' => 7,
                    'label' => 'Latitude',
                    'attr' => ['step' => 'any'],
                ])
                ->add('longitude', NumberType::class, [
                    'required' => false,
                    'scale' => 7,
                    'label' => 'Longitude',
                    'attr' => ['step' => 'any'],
                ])
        ;

        if ($options['is_mobile']) {
            $builder->add('mobileUploads', FileType::class, [
                'mapped'    => false,
                'multiple'  => true,
                'required'  => false,
                'label'     => 'Ajouter des photos',
                'attr'      => ['accept' => 'image/*', 'capture' => 'environment'],
            ]);
        } else {
            $builder->add('photos', CollectionType::class, [
                'entry_type'   => ClientPhotoType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'required'     => false,
                'label'        => 'Photos (max 10)',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'is_mobile'  => false,
        ]);
    }
}

