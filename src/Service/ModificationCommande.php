<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class ModificationCommande
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalculPrixCommande $calculPrixCommande,
        private CalculFraisLivraison $calculFraisLivraison
    ) {
    }

    public function calculerEmpreinte(Commande $commande): string
    {
        $etat = [
            'utilisateur' => $commande->getUtilisateur()?->getId(),
            'menu' => $commande->getMenu()?->getId(),
            'statut' => $commande->getStatut(),
            'nombre_personnes' => $commande->getNombrePersonnes(),
            'date_prestation' => $commande->getDatePrestation()
                ?->format('Y-m-d'),
            'heure_livraison' => $commande->getHeureLivraison()
                ?->format('H:i:s'),
            'adresse' => $commande->getAdresseLivraison(),
            'code_postal' => $commande->getCodePostalLivraison(),
            'ville' => $commande->getVilleLivraison(),
            'telephone' => $commande->getTelephoneContact(),
            'montant_menu' => $commande->getMontantMenuCentimes(),
            'remise' => $commande->getMontantRemiseCentimes(),
            'livraison' => $commande->getFraisLivraisonCentimes(),
            'total' => $commande->getMontantTotalCentimes(),
        ];

        return hash(
            'sha256',
            json_encode($etat, JSON_THROW_ON_ERROR)
        );
    }

    public function modifier(
        Commande $commande,
        Utilisateur $utilisateur,
        array $confirmation
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
                $confirmation,
                $menu
            ): bool {
                $em->refresh($menu, LockMode::PESSIMISTIC_WRITE);
                $em->refresh($commande, LockMode::PESSIMISTIC_WRITE);

                if (
                    $commande->getUtilisateur()?->getId()
                    !== $utilisateur->getId()
                ) {
                    throw new \DomainException(
                        'Vous ne pouvez pas modifier cette commande.'
                    );
                }

                if ($commande->getStatut() !== 'en_attente') {
                    throw new \DomainException(
                        'Cette commande ne peut plus être modifiée en ligne. Contactez notre équipe.'
                    );
                }

                if (
                    $commande->getMenu()?->getId() !== $menu->getId()
                    || $confirmation['menu_id'] !== $menu->getId()
                    || $confirmation['commande_id'] !== $commande->getId()
                    || $confirmation['utilisateur_id'] !== $utilisateur->getId()
                ) {
                    throw new \DomainException(
                        'Cette confirmation ne correspond plus à la commande.'
                    );
                }

                if ($confirmation['expire_a'] <= time()) {
                    throw new \DomainException(
                        'Cette confirmation a expiré. Recommencez la modification.'
                    );
                }

                $empreinteActuelle = $this->calculerEmpreinte(
                    $commande
                );

                if (!hash_equals(
                    $confirmation['empreinte_source'],
                    $empreinteActuelle
                )) {
                    throw new \DomainException(
                        'La commande a changé depuis votre saisie. Rechargez-la avant de recommencer.'
                    );
                }

                $em->refresh($utilisateur);

                if (!$utilisateur->isActif()) {
                    throw new \DomainException(
                        'Ce compte est désactivé.'
                    );
                }

                $donnees = $confirmation['donnees'];
                $recapitulatif = $confirmation['recapitulatif'];
                $nombrePersonnes = $donnees['nombre_personnes'];

                if (
                    $nombrePersonnes < $menu->getMinimumPersonnes()
                    || $nombrePersonnes > 999999
                ) {
                    throw new \DomainException(
                        'Le nombre de personnes est invalide pour ce menu.'
                    );
                }

                $fuseau = new \DateTimeZone('Europe/Paris');

                $datePrestation = new \DateTimeImmutable(
                    $donnees['date_prestation'],
                    $fuseau
                );

                $dateMinimum = new \DateTimeImmutable(
                    'today',
                    $fuseau
                );

                $dateMinimum = $dateMinimum->modify(
                    sprintf('+%d days', $menu->getDelaiMinimumJours())
                );

                if ($datePrestation < $dateMinimum) {
                    throw new \DomainException(
                        'La date ne respecte plus le délai du menu.'
                    );
                }

                $adresseIdentique =
                    trim($donnees['adresse_livraison'])
                        === trim($commande->getAdresseLivraison())
                    && trim($donnees['code_postal_livraison'])
                        === trim($commande->getCodePostalLivraison())
                    && trim($donnees['ville_livraison'])
                        === trim($commande->getVilleLivraison());

                if ($adresseIdentique) {
                    $fraisLivraison = $commande
                        ->getFraisLivraisonCentimes();
                } else {
                    $fraisLivraison = $this->calculFraisLivraison
                        ->calculer(
                            $recapitulatif['dans_bordeaux'],
                            $recapitulatif['distance_metres'] ?? 0
                        );
                }

                $montantMenu = $this->calculPrixCommande
                    ->calculerMontantMenu($menu, $nombrePersonnes);

                $remise = $this->calculPrixCommande
                    ->calculerRemise($menu, $nombrePersonnes);

                $total = $this->calculPrixCommande->calculerTotal(
                    $menu,
                    $nombrePersonnes,
                    $fraisLivraison
                );

                if (
                    $montantMenu > 2147483647
                    || $fraisLivraison > 2147483647
                    || $total > 2147483647
                ) {
                    throw new \DomainException(
                        'Le montant dépasse la limite autorisée.'
                    );
                }

                if (
                    $recapitulatif['montant_menu'] !== $montantMenu
                    || $recapitulatif['remise'] !== $remise
                    || $recapitulatif['frais_livraison'] !== $fraisLivraison
                    || $recapitulatif['total'] !== $total
                ) {
                    throw new \DomainException(
                        'Le tarif a changé. Recalculez le récapitulatif avant de confirmer.'
                    );
                }

                $commande->setNombrePersonnes($nombrePersonnes);
                $commande->setDatePrestation($datePrestation);
                $commande->setHeureLivraison(
                    new \DateTimeImmutable(
                        '1970-01-01 ' . $donnees['heure_livraison'],
                        $fuseau
                    )
                );
                $commande->setAdresseLivraison(
                    $donnees['adresse_livraison']
                );
                $commande->setCodePostalLivraison(
                    $donnees['code_postal_livraison']
                );
                $commande->setVilleLivraison(
                    $donnees['ville_livraison']
                );
                $commande->setTelephoneContact(
                    $donnees['telephone_contact']
                );
                $commande->setMontantMenuCentimes($montantMenu);
                $commande->setMontantRemiseCentimes($remise);
                $commande->setFraisLivraisonCentimes($fraisLivraison);
                $commande->setMontantTotalCentimes($total);

                // Une saisie identique ne crée pas d’événement inutile.
                if (
                    $this->calculerEmpreinte($commande)
                    === $empreinteActuelle
                ) {
                    return false;
                }

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut('en_attente');
                $historique->setAuteur($utilisateur);
                $historique->setMotif(
                    'Modification de la prestation par le client avant acceptation.'
                );

                $em->persist($historique);

                return true;
            }
        );
    }
}