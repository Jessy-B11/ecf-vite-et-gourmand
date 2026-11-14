<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailCreationEmploye
{
    public function __construct(
        // Résoudre le transport seulement lors de l’envoi, après l’enregistrement du compte.
        #[Autowire(lazy: true)]
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'MAILER_FROM_EMAIL')]
        private string $mailerFromEmail
    ) {
    }

    public function envoyer(Utilisateur $employe): void
    {
        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, 'Vite & Gourmand'))
            ->to((string) $employe->getEmail())
            ->subject('Votre compte employé — Vite & Gourmand')
            ->text(sprintf(
                "Bonjour,\n\nVotre compte employé Vite & Gourmand a été créé par le gérant.\n"
                . "Vous pouvez vous connecter à votre espace employé à cette adresse :\n%s\n\n"
                . "Pour votre première connexion, demandez votre mot de passe initial au gérant.\n\n"
                . "L’équipe Vite & Gourmand",
                $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ));

        $this->mailer->send($email);
    }
}
