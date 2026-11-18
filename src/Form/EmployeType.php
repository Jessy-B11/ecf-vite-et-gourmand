<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmployeType extends AbstractType
{
    public function getParent(): string
    {
        return RegistrationFormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Conserver les mêmes règles que l’inscription pour les champs communs.
        $password = $builder->get('plainPassword');

        $builder
            ->remove('telephone')
            ->remove('adresse')
            ->remove('codePostal')
            ->remove('ville')
            ->remove('agreeTerms')
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'options' => ['attr' => $password->getOption('attr')],
                'first_options' => [
                    'label' => 'Mot de passe initial',
                    'help' => $password->getOption('help'),
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe initial',
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => $password->getOption('constraints'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'csrf_protection' => true,
            'csrf_token_id' => 'creation_employe',
            'allow_extra_fields' => false,
        ]);
    }
}
