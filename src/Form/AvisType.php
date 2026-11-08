<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AvisType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'placeholder' => 'Choisissez une note',
                'choices' => [
                    '1 / 5' => 1,
                    '2 / 5' => 2,
                    '3 / 5' => 3,
                    '4 / 5' => 4,
                    '5 / 5' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Choisissez une note.'),
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'trim' => true,
                'attr' => ['rows' => 5, 'maxlength' => 2000],
                'constraints' => [
                    new Assert\NotBlank(message: 'Écrivez un commentaire.'),
                    new Assert\Length(
                        max: 2000,
                        maxMessage: 'Votre commentaire est limité à {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
            'csrf_token_id' => 'deposer_avis',
        ]);
    }
}