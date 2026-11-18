<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ModificationCommandeEquipeType extends AbstractType
{
    public function getParent(): string
    {
        return CommandeType::class;
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('modeContact', ChoiceType::class, [
                'label' => 'Mode de contact utilisé',
                'mapped' => false,
                'placeholder' => 'Choisissez un mode de contact',
                'choices' => [
                    'Téléphone' => 'telephone',
                    'Email' => 'email',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez le mode de contact utilisé.'
                    ),
                ],
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de la modification',
                'mapped' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 2000,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez le motif de la modification.'
                    ),
                    new Assert\Length(
                        max: 2000,
                        maxMessage: 'Le motif ne doit pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('clientContacte', CheckboxType::class, [
                'label' => 'Je confirme avoir contacté le client avant cette modification.',
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'Le contact préalable avec le client est obligatoire.'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'method' => 'POST',
            'csrf_token_id' => 'modification_commande_equipe',
            'allow_extra_fields' => false,
        ]);
    }
}