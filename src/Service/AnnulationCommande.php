<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class AnnulationCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function annuler(
        Commande $commande,
        Utilisateur $utilisateur
    ): bool {
        $menu = $commande->getMenu();

        if (
            $commande->getId() === null
            || $utilisateur->getId() === null
            || $menu === null
            || $menu->getId() === null
        ) {
            throw new \InvalidArgumentException(
                'Les informations de la commande sont incomplètes.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $commande,
                $utilisateur,
                $menu
            ): bool {
                // Toujours verrouiller le menu avant la commande.
                $em->refresh($menu, LockMode::PESSIMISTIC_WRITE);
                $em->refresh($commande, LockMode::PESSIMISTIC_WRITE);

                if (
                    $commande->getUtilisateur()?->getId()
                    !== $utilisateur->getId()
                ) {
                    throw new \DomainException(
                        'Vous ne pouvez pas annuler cette commande.'
                    );
                }

                if ($commande->getMenu()?->getId() !== $menu->getId()) {
                    throw new \DomainException(
                        'La commande a changé. Rechargez sa page.'
                    );
                }

                // Un second envoi ne doit pas rendre le stock deux fois.
                if ($commande->getStatut() === 'annulee') {
                    return false;
                }

                if ($commande->getStatut() !== 'en_attente') {
                    throw new \DomainException(
                        'Cette commande ne peut plus être annulée en ligne. Contactez notre équipe.'
                    );
                }

                $stock = $menu->getStockCommandes();

                if (
                    $stock === null
                    || $stock < 0
                    || $stock >= 2147483647
                ) {
                    throw new \DomainException(
                        'Le stock ne permet pas cette opération. Contactez notre équipe.'
                    );
                }

                $commande->setStatut('annulee');
                $menu->setStockCommandes($stock + 1);

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('annulee');
                $historique->setAuteur($utilisateur);
                $historique->setMotif(
                    'Annulation demandée par le client avant acceptation.'
                );

                $em->persist($historique);

                return true;
            }
        );
    }
}