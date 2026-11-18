<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre nom.'),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre prénom.'),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre email.'),
                    new Assert\Email(message: 'Indiquez un email valide.'),
                    new Assert\Length(max: 254),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['autocomplete' => 'tel'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre téléphone.'),
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['autocomplete' => 'street-address'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre adresse.'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre code postal.'),
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['autocomplete' => 'address-level2'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Indiquez votre ville.'),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'help' => 'Au moins 10 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial.',
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez un mot de passe.'
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
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J’accepte les conditions générales de vente.',
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'Vous devez accepter les conditions générales de vente.'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}