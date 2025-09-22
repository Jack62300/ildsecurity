<?php
namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b
            ->add('plate', TextType::class, ['label' => 'Plaque'])
            ->add('model', TextType::class, ['label' => 'Modèle', 'required' => false])
            ->add('photoFile', VichImageType::class, [
                'required'      => false,
                'download_uri'  => true,
                'allow_delete'  => true,
                'image_uri'     => true,
                'asset_helper'  => true, // pour vich_uploader_asset()
                'label'         => 'Photo',
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Vehicle::class]);
    }
}
