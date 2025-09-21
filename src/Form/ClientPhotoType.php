<?php
namespace App\Form;

use App\Entity\ClientPhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ClientPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFile', VichImageType::class, [
            'required'     => false,
            'label'        => false,
            'download_uri' => false,  // déjà présent
            'image_uri'    => false,  // ⬅️ désactive l’aperçu auto de Vich
            'allow_delete' => true,
            'attr' => [
                'accept'  => 'image/*',
                'capture' => 'environment',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ClientPhoto::class]);
    }
}