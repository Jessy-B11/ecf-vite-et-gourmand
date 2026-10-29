<?php

namespace App\Service;

use App\Entity\Commande;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class EmailRetourMateriel
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function envoyer(Commande $commande): void
    {
        $utilisateur = $commande->getUtilisateur();

        if (
            $commande->getId() === null
            || $utilisateur === null
            || !$commande->isMaterielPrete()
            || $commande->getStatut() !== 'attente_retour_materiel'
        ) {
            throw new \InvalidArgumentException(
                'La commande doit être en attente de retour du matériel.'
            );
        }

        $email = (new TemplatedEmail())
            ->from(new Address(
                'bonjour@vite-et-gourmand.example',
                'Vite & Gourmand'
            ))
            ->to((string) $utilisateur->getEmail())
            ->subject(sprintf(
                'Retour du matériel — commande n°%d',
                $commande->getId()
            ))
            ->htmlTemplate('emails/retour_materiel.html.twig')
            ->context([
                'commande' => $commande,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error(
                'Échec de l’envoi de la demande de retour du matériel.',
                [
                    'commande_id' => $commande->getId(),
                    'exception' => $exception,
                ]
            );
        }
    }
}