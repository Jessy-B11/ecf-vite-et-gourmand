<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'help' => 'Au moins 10 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial.',
                    'constraints' => [
                        new Assert\NotBlank(
                            message: 'Indiquez un nouveau mot de passe.'
                        ),
                        new Assert\Length(
                            min: 10,
                            max: 4096,
                            minMessage: 'Utilisez au moins {{ limit }} caractères.',
                            maxMessage: 'Ce mot de passe est trop long.'
                        ),
                        new Assert\Regex(
                            pattern: '/\p{Lu}/u',
                            message: 'Ajoutez au moins une majuscule.'
                        ),
                        new Assert\Regex(
                            pattern: '/\p{Ll}/u',
                            message: 'Ajoutez au moins une minuscule.'
                        ),
                        new Assert\Regex(
                            pattern: '/[0-9]/',
                            message: 'Ajoutez au moins un chiffre.'
                        ),
                        new Assert\Regex(
                            pattern: '/[^\p{L}\p{N}\s]/u',
                            message: 'Ajoutez au moins un caractère spécial.'
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmez le nouveau mot de passe',
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}