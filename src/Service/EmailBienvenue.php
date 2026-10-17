<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailBienvenue
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function envoyer(Utilisateur $utilisateur): void
    {
        $email = (new Email())
            ->from('bonjour@vite-et-gourmand.example')
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