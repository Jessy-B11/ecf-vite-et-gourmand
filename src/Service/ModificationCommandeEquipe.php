<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\HistoriqueCommande;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class ModificationCommandeEquipe
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModificationCommande $modificationCommande,
        private CalculPrixCommande $calculPrixCommande,
        private CalculFraisLivraison $calculFraisLivraison,
        private ActualisationStatistiquesCommande $actualisationStatistiques
    ) {
    }

    public function modifier(
        Commande $commande,
        Utilisateur $auteur,
        array $confirmation
    ): bool {
        $modeContact = $confirmation['mode_contact'] ?? null;
        $motif = $confirmation['motif'] ?? null;

        if (($confirmation['client_contacte'] ?? false) !== true) {
            throw new \InvalidArgumentException(
                'Le contact préalable avec le client est obligatoire.'
            );
        }

        if (!in_array($modeContact, ['telephone', 'email'], true)) {
            throw new \InvalidArgumentException(
                'Le mode de contact est invalide.'
            );
        }

        if (
            !is_string($motif)
            || trim($motif) === ''
            || mb_strlen(trim($motif)) > 2000
        ) {
            throw new \InvalidArgumentException(
                'Indiquez un motif compris entre 1 et 2 000 caractères.'
            );
        }

        $motif = trim($motif);
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
                $confirmation,
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
                        'Ce compte ne peut pas modifier les commandes.'
                    );
                }

                if (!in_array(
                    $commande->getStatut(),
                    ['en_attente', 'acceptee', 'en_preparation'],
                    true
                )) {
                    throw new \DomainException(
                        'Cette commande ne peut plus être modifiée depuis cet écran.'
                    );
                }

                if (
                    $commande->getMenu()?->getId() !== $menu->getId()
                    || ($confirmation['menu_id'] ?? null)
                        !== $menu->getId()
                    || ($confirmation['commande_id'] ?? null)
                        !== $commande->getId()
                    || ($confirmation['auteur_id'] ?? null)
                        !== $auteur->getId()
                    || ($confirmation['utilisateur_id'] ?? null)
                        !== $commande->getUtilisateur()?->getId()
                ) {
                    throw new \DomainException(
                        'Cette confirmation ne correspond plus à la commande.'
                    );
                }

                if (($confirmation['expire_a'] ?? 0) <= time()) {
                    throw new \DomainException(
                        'Cette confirmation a expiré. Recommencez la modification.'
                    );
                }

                $empreinteActuelle = $this->modificationCommande
                    ->calculerEmpreinte($commande);

                if (!hash_equals(
                    $confirmation['empreinte_source'],
                    $empreinteActuelle
                )) {
                    throw new \DomainException(
                        'La commande a changé depuis votre saisie. Recommencez la modification.'
                    );
                }

                $donnees = $confirmation['donnees'];
                $recapitulatif = $confirmation['recapitulatif'];
                $nombrePersonnes = $donnees['nombre_personnes'];

                if (
                    !is_int($nombrePersonnes)
                    || $nombrePersonnes < $menu->getMinimumPersonnes()
                    || $nombrePersonnes > 999999
                ) {
                    throw new \DomainException(
                        'Le nombre de personnes est invalide pour ce menu.'
                    );
                }

                $fuseau = new \DateTimeZone('Europe/Paris');

                $datePrestation = \DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $donnees['date_prestation'],
                    $fuseau
                );

                if (
                    $datePrestation === false
                    || $datePrestation->format('Y-m-d')
                        !== $donnees['date_prestation']
                ) {
                    throw new \DomainException(
                        'La date de prestation est invalide.'
                    );
                }

                $aujourdhui = new \DateTimeImmutable('today', $fuseau);

                if ($datePrestation < $aujourdhui) {
                    throw new \DomainException(
                        'La prestation ne peut pas être placée dans le passé.'
                    );
                }

                $dateChangee =
                    $datePrestation->format('Y-m-d')
                    !== $commande->getDatePrestation()?->format('Y-m-d');

                $dateMinimum = $aujourdhui->modify(
                    sprintf('+%d days', $menu->getDelaiMinimumJours())
                );

                if ($dateChangee && $datePrestation < $dateMinimum) {
                    throw new \DomainException(
                        'La nouvelle date ne respecte pas le délai du menu.'
                    );
                }

                $heureLivraison = \DateTimeImmutable::createFromFormat(
                    '!H:i',
                    $donnees['heure_livraison'],
                    $fuseau
                );

                if (
                    $heureLivraison === false
                    || $heureLivraison->format('H:i')
                        !== $donnees['heure_livraison']
                ) {
                    throw new \DomainException(
                        'L’heure de livraison est invalide.'
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
                    $dansBordeaux = $recapitulatif['dans_bordeaux'] ?? null;
                    $distance = $recapitulatif['distance_metres'] ?? null;

                    if (
                        !is_bool($dansBordeaux)
                        || (
                            !$dansBordeaux
                            && (
                                !is_int($distance)
                                || $distance < 0
                                || $distance > 100000
                            )
                        )
                    ) {
                        throw new \DomainException(
                            'Les informations de livraison doivent être recalculées.'
                        );
                    }

                    $fraisLivraison = $this->calculFraisLivraison
                        ->calculer(
                            $dansBordeaux,
                            $dansBordeaux ? 0 : $distance
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

                $montants = [
                    'montant_menu' => $montantMenu,
                    'remise' => $remise,
                    'frais_livraison' => $fraisLivraison,
                    'total' => $total,
                ];

                foreach ($montants as $champ => $montant) {
                    if ($montant < 0 || $montant > 2147483647) {
                        throw new \DomainException(
                            'Un montant dépasse les limites autorisées.'
                        );
                    }

                    if (($recapitulatif[$champ] ?? null) !== $montant) {
                        throw new \DomainException(
                            'Le tarif a changé. Recalculez le récapitulatif avant de confirmer.'
                        );
                    }
                }

                $commande->setNombrePersonnes($nombrePersonnes);
                $commande->setDatePrestation($datePrestation);
                $commande->setHeureLivraison($heureLivraison);
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

                if (
                    $this->modificationCommande
                        ->calculerEmpreinte($commande)
                    === $empreinteActuelle
                ) {
                    return false;
                }

                $historique = new HistoriqueCommande();
                $historique->setCommande($commande);
                $historique->setStatut($commande->getStatut());
                $historique->setAuteur($auteur);
                $historique->setModeContact($modeContact);
                $historique->setMotif(
                    'Modification par l’équipe après contact client : '
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