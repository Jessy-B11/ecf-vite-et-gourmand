<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class EnregistrementCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalculPrixCommande $calculPrixCommande
    ) {
    }

    public function enregistrer(Commande $commande): Commande
    {
        $menu = $commande->getMenu();
        $utilisateur = $commande->getUtilisateur();
        $jeton = $commande->getJetonValidation();

        if (
            $commande->getId() !== null
            || $menu === null
            || $utilisateur === null
            || $menu->getId() === null
            || $utilisateur->getId() === null
            || $jeton === null
            || !preg_match('/^[a-f0-9]{32}$/D', $jeton)
        ) {
            throw new \InvalidArgumentException(
                'Les informations de la commande sont incomplètes.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $commande,
                $menu,
                $utilisateur,
                $jeton
            ): Commande {
                // Relire le menu et verrouiller sa ligne jusqu’à
                // la fin de la transaction.
                $em->refresh($menu, LockMode::PESSIMISTIC_WRITE);

                // Un second envoi du même brouillon renvoie
                // la commande déjà créée, sans toucher au stock.
                $existante = $em->getRepository(Commande::class)
                    ->findOneBy(['jetonValidation' => $jeton]);

                if ($existante !== null) {
                    if (
                        $existante->getUtilisateur()?->getId()
                            !== $utilisateur->getId()
                        || $existante->getMenu()?->getId()
                            !== $menu->getId()
                    ) {
                        throw new \DomainException(
                            'Cette validation ne correspond pas à votre commande.'
                        );
                    }

                    return $existante;
                }

                $em->refresh($utilisateur);

                if (!$utilisateur->isActif()) {
                    throw new \DomainException(
                        'Ce compte est désactivé.'
                    );
                }

                if ($menu->getStockCommandes() <= 0) {
                    throw new \DomainException(
                        'Ce menu est désormais indisponible.'
                    );
                }

                $nombrePersonnes = $commande->getNombrePersonnes();

                if (
                    $nombrePersonnes === null
                    || $nombrePersonnes < $menu->getMinimumPersonnes()
                    || $nombrePersonnes > 999999
                ) {
                    throw new \DomainException(
                        'Le nombre de personnes est invalide pour ce menu.'
                    );
                }

                $dateMinimum = new \DateTimeImmutable(
                    'today',
                    new \DateTimeZone('Europe/Paris')
                );

                $dateMinimum = $dateMinimum->modify(
                    sprintf('+%d days', $menu->getDelaiMinimumJours())
                );

                $datePrestation = $commande->getDatePrestation();

                if (
                    $datePrestation === null
                    || $datePrestation->format('Y-m-d')
                        < $dateMinimum->format('Y-m-d')
                ) {
                    throw new \DomainException(
                        'La date ne respecte plus le délai du menu.'
                    );
                }

                // Recalculer avec le prix actuel du menu.
                $montantMenu = $this->calculPrixCommande
                    ->calculerMontantMenu($menu, $nombrePersonnes);

                $remise = $this->calculPrixCommande
                    ->calculerRemise($menu, $nombrePersonnes);

                $fraisLivraison = $commande->getFraisLivraisonCentimes();

                if ($fraisLivraison === null || $fraisLivraison < 0) {
                    throw new \DomainException(
                        'Les frais de livraison sont invalides.'
                    );
                }

                $total = $this->calculPrixCommande->calculerTotal(
                    $menu,
                    $nombrePersonnes,
                    $fraisLivraison
                );

                // Respecter les limites des colonnes INTEGER PostgreSQL.
                if (
                    $montantMenu > 2147483647
                    || $fraisLivraison > 2147483647
                    || $total > 2147483647
                ) {
                    throw new \DomainException(
                        'Le montant dépasse la limite autorisée.'
                    );
                }

                // Ne pas enregistrer un prix différent de celui
                // présenté au client dans son récapitulatif.
                if (
                    $commande->getMontantMenuCentimes() !== $montantMenu
                    || $commande->getMontantRemiseCentimes() !== $remise
                    || $commande->getMontantTotalCentimes() !== $total
                ) {
                    throw new \DomainException(
                        'Le tarif a changé. Recalculez votre récapitulatif avant de confirmer.'
                    );
                }

                $commande->setStatut('en_attente');

                $menu->setStockCommandes(
                    $menu->getStockCommandes() - 1
                );

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('en_attente');
                $historique->setAuteur($utilisateur);

                $em->persist($commande);
                $em->persist($historique);

                return $commande;
            }
        );
    }
}