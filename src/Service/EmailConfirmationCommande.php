<?php

namespace App\Service;

use App\Entity\Commande;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class EmailConfirmationCommande
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function envoyer(Commande $commande): void
    {
        $utilisateur = $commande->getUtilisateur();

        if ($commande->getId() === null || $utilisateur === null) {
            throw new \InvalidArgumentException(
                'La commande doit être enregistrée avant l’envoi du mail.'
            );
        }

        $email = (new TemplatedEmail())
            ->from(new Address(
                'bonjour@vite-et-gourmand.example',
                'Vite & Gourmand'
            ))
            ->to((string) $utilisateur->getEmail())
            ->subject(sprintf(
                'Votre commande n°%d est enregistrée',
                $commande->getId()
            ))
            ->htmlTemplate('emails/confirmation_commande.html.twig')
            ->context([
                'commande' => $commande,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error(
                'Échec de l’envoi de la confirmation de commande.',
                [
                    'commande_id' => $commande->getId(),
                    'exception' => $exception,
                ]
            );
        }
    }
}