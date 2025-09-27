<?php

namespace App\Form;

use App\Entity\PhoneCategory;
use App\Entity\PhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => PhoneCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => '— Choisir —',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom / Service',
            ])
            ->add('number', TelType::class, [
                'label' => 'Numéro',
                'attr'  => ['inputmode' => 'tel', 'placeholder' => '+33 1 23 45 67 89'],
            ])
            ->add('notes', TextType::class, [
                'label' => 'Notes (optionnel)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PhoneNumber::class]);
    }
}
