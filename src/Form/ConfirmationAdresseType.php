<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ConfirmationAdresseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $adresses = $options['adresses'];

        $builder->add('adresse', ChoiceType::class, [
            'label' => 'Choisissez votre adresse de livraison',
            'choices' => array_keys($adresses),
            'choice_label' => static function (
                int $index
            ) use ($adresses): string {
                return $adresses[$index]['properties']['label'];
            },
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'invalid_message' => 'Cette adresse ne fait pas partie des propositions.',
            'constraints' => [
                new NotNull(
                    message: 'Sélectionnez une adresse de livraison.'
                ),
            ],
        ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setRequired('adresses');
        $resolver->setAllowedTypes('adresses', 'array');

        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id' => 'confirmation_adresse_livraison',
        ]);
    }
}