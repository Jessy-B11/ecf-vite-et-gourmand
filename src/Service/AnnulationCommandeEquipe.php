<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class AnnulationCommandeEquipe
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActualisationStatistiquesCommande $actualisationStatistiques
    ) {
    }

    public function annuler(
        Commande $commande,
        Utilisateur $auteur,
        string $modeContact,
        string $motif,
        bool $clientContacte
    ): bool {
        $motif = trim($motif);

        if (!$clientContacte) {
            throw new \InvalidArgumentException(
                'Vous devez contacter le client avant l’annulation.'
            );
        }

        if (!in_array($modeContact, ['telephone', 'email'], true)) {
            throw new \InvalidArgumentException(
                'Choisissez un mode de contact valide.'
            );
        }

        if ($motif === '' || mb_strlen($motif) > 2000) {
            throw new \InvalidArgumentException(
                'Indiquez un motif compris entre 1 et 2 000 caractères.'
            );
        }

        $menu = $commande->getMenu();

        if (
            $commande->getId() === null
            || $auteur->getId() === null
            || $menu === null
            || $menu->getId() === null
        ) {
            throw new \InvalidArgumentException(
                'Les informations de la commande sont incomplètes.'
            );
        }

        $resultat = $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $commande,
                $auteur,
                $menu,
                $modeContact,
                $motif
            ): bool {
                $em->refresh($menu, LockMode::PESSIMISTIC_WRITE);
                $em->refresh($commande, LockMode::PESSIMISTIC_WRITE);
                $em->refresh($auteur);

                $roles = $auteur->getRoles();

                if (
                    !$auteur->isActif()
                    || (
                        !in_array('ROLE_EMPLOYE', $roles, true)
                        && !in_array('ROLE_ADMIN', $roles, true)
                    )
                ) {
                    throw new \DomainException(
                        'Ce compte ne peut pas annuler les commandes.'
                    );
                }

                if ($commande->getMenu()?->getId() !== $menu->getId()) {
                    throw new \DomainException(
                        'La commande a changé. Rechargez sa page.'
                    );
                }

                if ($commande->getStatut() === 'annulee') {
                    return false;
                }

                if (!in_array(
                    $commande->getStatut(),
                    ['en_attente', 'acceptee', 'en_preparation'],
                    true
                )) {
                    throw new \DomainException(
                        'Cette commande ne peut plus être annulée depuis cet écran.'
                    );
                }

                $stock = $menu->getStockCommandes();

                if (
                    $stock === null
                    || $stock < 0
                    || $stock >= 2147483647
                ) {
                    throw new \DomainException(
                        'Le stock ne permet pas cette opération.'
                    );
                }

                $commande->setStatut('annulee');
                $menu->setStockCommandes($stock + 1);

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('annulee');
                $historique->setAuteur($auteur);
                $historique->setModeContact($modeContact);
                $historique->setMotif(
                    'Annulation par l’équipe après contact client : '
                    . $motif
                );

                $em->persist($historique);

                return true;
            }
        );

        $this->actualisationStatistiques->apresValidation($commande->getId());

        return $resultat;
    }
}