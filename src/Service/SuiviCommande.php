<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class SuiviCommande
{
    private const TRANSITIONS = [
        'en_preparation' => [
            'source' => 'acceptee',
            'motif' => 'Préparation de la commande commencée.',
        ],
        'en_livraison' => [
            'source' => 'en_preparation',
            'motif' => 'Livraison de la commande commencée.',
        ],
        'livree' => [
            'source' => 'en_livraison',
            'motif' => 'Livraison de la commande confirmée.',
        ],
                'terminee' => [
            'source' => 'livree',
            'motif' => 'Commande terminée sans matériel à restituer.',
        ],
                'attente_retour_materiel' => [
            'source' => 'livree',
            'motif' => 'Commande en attente de restitution du matériel prêté.',
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function changerStatut(
        Commande $commande,
        Utilisateur $auteur,
        string $nouveauStatut
    ): bool {
        if (!isset(self::TRANSITIONS[$nouveauStatut])) {
            throw new \InvalidArgumentException(
                'Cette transition n’est pas autorisée.'
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

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $commande,
                $auteur,
                $menu,
                $nouveauStatut
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
                        'Ce compte ne peut pas gérer les commandes.'
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

                if ($commande->getStatut() === $nouveauStatut) {
                    return false;
                }

                $transition = self::TRANSITIONS[$nouveauStatut];

                if (
                    $commande->getStatut()
                    !== $transition['source']
                ) {
                    throw new \DomainException(
                        'Le statut actuel ne permet pas cette action. Rechargez la commande.'
                    );
                }

                if (
                    $nouveauStatut === 'terminee'
                    && $commande->isMaterielPrete()
                ) {
                    throw new \DomainException(
                        'Le matériel prêté doit être restitué avant de terminer cette commande.'
                    );
                }

                if (
                    $nouveauStatut === 'attente_retour_materiel'
                    && (
                        !$commande->isMaterielPrete()
                        || $commande->getDateRetourMateriel() !== null
                    )
                ) {
                    throw new \DomainException(
                        'Cette commande ne comporte pas de matériel à restituer.'
                    );
                }

                $commande->setStatut($nouveauStatut);

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut($nouveauStatut);
                $historique->setAuteur($auteur);
                $historique->setMotif($transition['motif']);

                $em->persist($historique);

                return true;
            }
        );
    }
}