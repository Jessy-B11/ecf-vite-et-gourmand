<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ProfilType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre nom.'
                    ),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre prénom.'
                    ),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['autocomplete' => 'tel'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre téléphone.'
                    ),
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['autocomplete' => 'street-address'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre adresse.'
                    ),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre code postal.'
                    ),
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['autocomplete' => 'address-level2'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Indiquez votre ville.'
                    ),
                    new Assert\Length(max: 100),
                ],
            ])
        ;
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'csrf_token_id' => 'modifier_profil',
            'allow_extra_fields' => false,
        ]);
    }
}