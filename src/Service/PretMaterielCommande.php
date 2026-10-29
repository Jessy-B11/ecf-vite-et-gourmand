<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class PretMaterielCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function definir(
        Commande $commande,
        Utilisateur $auteur,
        bool $materielPrete
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
                $menu,
                $materielPrete
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
                        'Ce compte ne peut pas gérer le matériel.'
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

                if (!in_array(
                    $commande->getStatut(),
                    ['en_attente', 'acceptee'],
                    true
                )) {
                    throw new \DomainException(
                        'Le prêt de matériel ne peut plus être modifié à cette étape.'
                    );
                }

                if ($commande->getDateRetourMateriel() !== null) {
                    throw new \DomainException(
                        'Un retour de matériel est déjà enregistré.'
                    );
                }

                if ($commande->isMaterielPrete() === $materielPrete) {
                    return false;
                }

                $commande->setMaterielPrete($materielPrete);

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut($commande->getStatut());
                $historique->setAuteur($auteur);
                $historique->setMotif(
                    $materielPrete
                        ? 'Prêt de matériel prévu pour cette commande.'
                        : 'Prêt de matériel retiré de cette commande avant préparation.'
                );

                $em->persist($historique);

                return true;
            }
        );
    }
}