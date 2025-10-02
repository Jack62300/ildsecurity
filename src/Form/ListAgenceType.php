<?php

namespace App\Form;

use App\Entity\ListAgence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ListAgenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom de l’agence',
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('codeAgence', null, [
                'label' => 'Code agence',
                'help'  => 'Ex: PARIS-01, LYO-02…',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 50)]
            ])
            ->add('email', null, [
                'label' => 'Email reliez à l\'agence',
                'help'  => 'Ex: intervenant.lille@ildsecurity.fr',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ListAgence::class]);
    }
}
