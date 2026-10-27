<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommandeType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('nombrePersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'min' => $options['minimum_personnes'],
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Indiquez le nombre de personnes.'
                    ),
                    new GreaterThanOrEqual(
                        value: $options['minimum_personnes'],
                        message: 'Ce menu nécessite au moins {{ compared_value }} personnes.'
                    ),
                ],
            ])
            ->add('datePrestation', DateType::class, [
                'label' => 'Date de la prestation',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
                'attr' => [
                    'min' => $options['date_minimum']->format('Y-m-d'),
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Choisissez une date de prestation.'
                    ),
                    new GreaterThanOrEqual(
                        value: $options['date_minimum'],
                        message: 'La date choisie ne respecte pas le délai minimum du menu.'
                    ),
                ],
            ])
            ->add('heureLivraison', TimeType::class, [
                'label' => 'Heure souhaitée de livraison',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'with_seconds' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'Choisissez une heure de livraison.'
                    ),
                ],
            ])
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse de livraison',
                'attr' => [
                    'autocomplete' => 'street-address',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Indiquez une adresse de livraison.'
                    ),
                    new Length(max: 255),
                ],
            ])
            ->add('codePostalLivraison', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'autocomplete' => 'postal-code',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Indiquez un code postal.'
                    ),
                    new Length(max: 20),
                ],
            ])
            ->add('villeLivraison', TextType::class, [
                'label' => 'Ville de livraison',
                'attr' => [
                    'autocomplete' => 'address-level2',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Indiquez une ville de livraison.'
                    ),
                    new Length(max: 100),
                ],
            ])
            ->add('telephoneContact', TelType::class, [
                'label' => 'Téléphone de contact',
                'attr' => [
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Indiquez un numéro de téléphone.'
                    ),
                    new Length(max: 20),
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'minimum_personnes' => 1,
        ]);

        $resolver->setAllowedTypes('minimum_personnes', 'int');

        $resolver->setAllowedValues(
            'minimum_personnes',
            static fn (int $minimum): bool => $minimum > 0
        );

        $resolver->setRequired('date_minimum');

        $resolver->setAllowedTypes(
            'date_minimum',
            \DateTimeImmutable::class
        );
    }
}