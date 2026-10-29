<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class RetourMaterielCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function confirmer(
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
                        'Ce compte ne peut pas confirmer une restitution.'
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

                if (!$commande->isMaterielPrete()) {
                    throw new \DomainException(
                        'Cette commande ne comporte pas de prêt de matériel.'
                    );
                }

                if (
                    $commande->getStatut() === 'terminee'
                    && $commande->getDateRetourMateriel() !== null
                ) {
                    return false;
                }

                if (
                    $commande->getStatut() !== 'attente_retour_materiel'
                    || $commande->getDateRetourMateriel() !== null
                ) {
                    throw new \DomainException(
                        'Le statut actuel ne permet pas de confirmer la restitution.'
                    );
                }

                $dateRetour = new \DateTimeImmutable(
                    'now',
                    new \DateTimeZone('Europe/Paris')
                );

                $commande->setDateRetourMateriel($dateRetour);
                $commande->setStatut('terminee');

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('terminee');
                $historique->setAuteur($auteur);
                $historique->setDateChangement($dateRetour);
                $historique->setMotif(
                    'Restitution du matériel confirmée par l’équipe ; commande terminée.'
                );

                $em->persist($historique);

                return true;
            }
        );
    }
}