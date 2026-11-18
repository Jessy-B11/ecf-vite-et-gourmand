<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class EmailBienvenue
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM_EMAIL')]
        private string $mailerFromEmail
    ) {
    }

    public function envoyer(Utilisateur $utilisateur): void
    {
        $email = (new Email())
            ->from(new Address($this->mailerFromEmail, 'Vite & Gourmand'))
            ->to((string) $utilisateur->getEmail())
            ->subject('Bienvenue chez Vite & Gourmand')
            ->text(sprintf(
                "Bonjour %s,\n\n"
                . "Votre compte Vite & Gourmand a bien été créé.\n"
                . "Vous pouvez vous connecter pour découvrir nos menus "
                . "et préparer vos prochaines commandes.\n\n"
                . "À bientôt,\n"
                . "Julie et José\n"
                . "Vite & Gourmand",
                $utilisateur->getPrenom() ?? ''
            ));

        $this->mailer->send($email);
    }
}
