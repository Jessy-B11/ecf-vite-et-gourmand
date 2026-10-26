<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class AcceptationCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function accepter(
        Commande $commande,
        Utilisateur $auteur
    ): bool {
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

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $commande,
                $auteur,
                $menu
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
                        'Ce compte ne peut pas accepter de commande.'
                    );
                }

                if (
                    $commande->getMenu()?->getId()
                    !== $menu->getId()
                ) {
                    throw new \DomainException(
                        'Le menu de la commande a changé. Rechargez la page.'
                    );
                }

                if ($commande->getStatut() === 'acceptee') {
                    return false;
                }

                if ($commande->getStatut() !== 'en_attente') {
                    throw new \DomainException(
                        'Seule une commande en attente peut être acceptée.'
                    );
                }

                $commande->setStatut('acceptee');

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('acceptee');
                $historique->setAuteur($auteur);
                $historique->setMotif(
                    'Commande acceptée par un membre de l’équipe.'
                );

                $em->persist($historique);

                return true;
            }
        );
    }
}