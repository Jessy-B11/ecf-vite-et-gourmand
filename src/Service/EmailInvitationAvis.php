<?php

namespace App\Service;

use App\Entity\Commande;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailInvitationAvis
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
    }

    public function envoyer(Commande $commande): void
    {
        $id = $commande->getId();
        $destinataire = $commande->getUtilisateur()?->getEmail();

        if ($id === null || $destinataire === null || $destinataire === '') {
            $this->logger->warning(
                'Invitation à laisser un avis impossible : commande ou destinataire manquant.',
                ['commande_id' => $id]
            );

            return;
        }

        $lien = $this->urlGenerator->generate(
            'app_commande_avis',
            ['id' => $id],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from(new Address(
                'bonjour@vite-et-gourmand.example',
                'Vite & Gourmand'
            ))
            ->to($destinataire)
            ->subject(sprintf('Votre avis sur la commande n°%d', $id))
            ->text(sprintf(
                "Bonjour,\n\nMerci d’avoir commandé chez Vite & Gourmand."
                . "\nVous pouvez partager votre expérience en suivant ce lien :\n%s"
                . "\n\nL’équipe Vite & Gourmand",
                $lien
            ));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error(
                'Échec de l’envoi de l’invitation à laisser un avis.',
                ['commande_id' => $id, 'exception' => $exception]
            );
        }
    }
}