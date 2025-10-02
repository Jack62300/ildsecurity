<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as T;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\EntityCatalog;

final class ExportFormType extends AbstractType
{
    public function __construct(private EntityCatalog $catalog) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->catalog->listExportables();
        $defaults = $this->catalog->defaultSelected();

        $builder
             ->add('entities', T\ChoiceType::class, [
                'label' => 'Sélectionnez les tables à exporter',
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'data' => $defaults,                 // ✅ valeurs valides
                'choice_translation_domain' => false,
            ])
            // ✅ plus de "format" : on force CSV
            ->add('csv_delimiter', T\TextType::class, [
                'label' => 'Délimiteur CSV',
                'empty_data' => ';',
                'required' => false,
            ])
            ->add('limit', T\IntegerType::class, [
                'label' => 'Limite de lignes (0 = illimité)',
                'empty_data' => '0',
                'required' => false,
            ])
            ->add('submit', T\SubmitType::class, ['label' => 'Exporter en CSV']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => true]);
    }
}
