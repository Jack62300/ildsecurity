<?php
// src/Form/UserType.php
namespace App\Form;

use App\Entity\User;
use App\Repository\ListAgenceRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function __construct(private ListAgenceRepository $agences) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = (bool) $options['is_edit'];

        // label => value (value = code agence, string)
        $choices = [];
        foreach ($this->agences->findBy([], ['name' => 'ASC']) as $a) {
            $choices[sprintf('%s (%s)', $a->getName(), $a->getCodeAgence())] = $a->getCodeAgence();
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d’utilisateur',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('agence', ChoiceType::class, [
                'label' => 'Agence',
                'placeholder' => '— Sélectionner une agence —',
                'choices' => $choices,            // <- string stocké dans User.agence
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Responsable'     => User::ROLE_ADMIN,
                    'Développeur'       => User::ROLE_DEV,
                    'Administrateur'   => User::ROLE_SUPPORT,
                    'Agent Mobile' => User::ROLE_OPERATEUR,
                    'Utilateur'      => User::ROLE_USER,
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => $isEdit ? [] : [new Assert\NotBlank(), new Assert\Length(min: 6)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
