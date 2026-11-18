<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\ConfirmationAdresseType;
use App\Repository\CommandeRepository;
use App\Service\CalculFraisLivraison;
use App\Service\CalculPrixCommande;
use App\Service\GeocodageAdresse;
use App\Service\ItineraireLivraison;
use App\Service\ModificationCommande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
final class RecapitulatifModificationCommandeEquipeController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'LIVRAISON_COMMUNE_GRATUITE')]
        private string $communeGratuite
    ) {
    }

    #[Route(
        '/gestion/commandes/modification/recapitulatif/{jeton}',
        name: 'app_gestion_modification_commande_recapitulatif',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['GET', 'POST']
    )]
    public function index(
        string $jeton,
        Request $request,
        CommandeRepository $commandeRepository,
        ModificationCommande $modificationCommande,
        GeocodageAdresse $geocodageAdresse,
        ItineraireLivraison $itineraireLivraison,
        CalculFraisLivraison $calculFraisLivraison,
        CalculPrixCommande $calculPrixCommande
    ): Response {
        $auteur = $this->getUser();

        if (
            !$auteur instanceof Utilisateur
            || !$auteur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();

        $brouillon = $session->get(
            'modification_commande_equipe_brouillon'
        );

        if (
            !is_array($brouillon)
            || $brouillon['auteur_id'] !== $auteur->getId()
            || !hash_equals($brouillon['jeton'], $jeton)
            || $brouillon['expire_a'] <= time()
        ) {
            throw $this->createNotFoundException(
                'Cette demande est indisponible ou a expiré.'
            );
        }

        $commande = $commandeRepository->find(
            $brouillon['commande_id']
        );

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        $menu = $commande->getMenu();

        if (
            $menu === null
            || $menu->getId() !== $brouillon['menu_id']
            || $commande->getUtilisateur()?->getId()
                !== $brouillon['utilisateur_id']
            || !in_array(
                $commande->getStatut(),
                ['en_attente', 'acceptee', 'en_preparation'],
                true
            )
            || !hash_equals(
                $brouillon['empreinte_source'],
                $modificationCommande->calculerEmpreinte($commande)
            )
        ) {
            $session->remove(
                'modification_commande_equipe_brouillon'
            );
            $session->remove(
                'modification_commande_equipe_confirmation'
            );

            $this->addFlash(
                'warning',
                'La commande a changé. Recommencez la modification.'
            );

            return $this->redirectToRoute(
                'app_gestion_commandes_show',
                ['id' => $commande->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        // Recalculer invalide la confirmation précédemment préparée.
        $session->remove(
            'modification_commande_equipe_confirmation'
        );

        $donnees = $brouillon['donnees'];
        $adresseIdentique = $this->adresseIdentique(
            $commande,
            $donnees
        );

        $form = null;
        $recapitulatif = null;
        $erreur = null;
        $dansBordeaux = null;
        $distanceMetres = null;
        $pretPourCalcul = $adresseIdentique;

        try {
            if (!$adresseIdentique) {
                if (!isset($brouillon['adresses'])) {
                    $adresseComplete = sprintf(
                        '%s %s %s',
                        $donnees['adresse_livraison'],
                        $donnees['code_postal_livraison'],
                        $donnees['ville_livraison']
                    );

                    $adresses = $geocodageAdresse->rechercher(
                        $adresseComplete
                    );

                    if ($adresses === []) {
                        throw new \DomainException(
                            'Aucune adresse précise trouvée. Recommencez la saisie en vérifiant le numéro et la rue.'
                        );
                    }

                    $brouillon['adresses'] = $adresses;

                    $session->set(
                        'modification_commande_equipe_brouillon',
                        $brouillon
                    );
                }

                $form = $this->createForm(
                    ConfirmationAdresseType::class,
                    null,
                    [
                        'adresses' => $brouillon['adresses'],
                        'csrf_token_id' => 'adresse_modification_equipe_' . $jeton,
                    ]
                );

                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $index = $form->get('adresse')->getData();
                    $adresse = $brouillon['adresses'][$index];

                    $proprietes = $adresse['properties'] ?? [];

                    foreach (
                        ['name', 'postcode', 'city', 'citycode']
                        as $champ
                    ) {
                        if (
                            !isset($proprietes[$champ])
                            || !is_string($proprietes[$champ])
                            || trim($proprietes[$champ]) === ''
                        ) {
                            throw new \RuntimeException(
                                'Les informations de cette adresse sont incomplètes.'
                            );
                        }
                    }

                    $donnees['adresse_livraison'] = $proprietes['name'];
                    $donnees['code_postal_livraison'] = $proprietes['postcode'];
                    $donnees['ville_livraison'] = $proprietes['city'];

                    $adresseIdentique = $this->adresseIdentique(
                        $commande,
                        $donnees
                    );

                    $dansBordeaux =
                        $proprietes['citycode'] === $this->communeGratuite;

                    if (!$adresseIdentique && !$dansBordeaux) {
                        $coordonnees = $adresse['geometry']['coordinates']
                            ?? null;

                        if (
                            !is_array($coordonnees)
                            || count($coordonnees) !== 2
                            || !is_numeric($coordonnees[0])
                            || !is_numeric($coordonnees[1])
                        ) {
                            throw new \RuntimeException(
                                'Les coordonnées de cette adresse sont invalides.'
                            );
                        }

                        $longitude = (float) $coordonnees[0];
                        $latitude = (float) $coordonnees[1];

                        if (
                            !is_finite($longitude)
                            || !is_finite($latitude)
                            || $longitude < -180
                            || $longitude > 180
                            || $latitude < -90
                            || $latitude > 90
                        ) {
                            throw new \RuntimeException(
                                'Les coordonnées de cette adresse sont invalides.'
                            );
                        }

                        $distanceMetres = $itineraireLivraison
                            ->calculerDistance($longitude, $latitude);

                        if (
                            $distanceMetres < 0
                            || $distanceMetres > 100000
                        ) {
                            throw new \DomainException(
                                'Cette adresse dépasse la zone de livraison autorisée de 100 km.'
                            );
                        }
                    }

                    $pretPourCalcul = true;
                }
            }

            if ($pretPourCalcul) {
                $nombrePersonnes = $donnees['nombre_personnes'];

                if (
                    $nombrePersonnes < $menu->getMinimumPersonnes()
                    || $nombrePersonnes > 999999
                ) {
                    throw new \DomainException(
                        'Le nombre de personnes ne respecte plus les conditions du menu.'
                    );
                }

                $fuseau = new \DateTimeZone('Europe/Paris');
                $aujourdhui = new \DateTimeImmutable('today', $fuseau);

                $datePrestation = new \DateTimeImmutable(
                    $donnees['date_prestation'],
                    $fuseau
                );

                $dateMinimum = $aujourdhui->modify(
                    sprintf('+%d days', $menu->getDelaiMinimumJours())
                );

                $dateChangee =
                    $donnees['date_prestation']
                    !== $commande->getDatePrestation()->format('Y-m-d');

                if (
                    $datePrestation < $aujourdhui
                    || ($dateChangee && $datePrestation < $dateMinimum)
                ) {
                    throw new \DomainException(
                        'La date ne respecte plus les conditions de la commande.'
                    );
                }

                if ($adresseIdentique) {
                    $fraisLivraison = $commande
                        ->getFraisLivraisonCentimes();
                } else {
                    $fraisLivraison = $calculFraisLivraison->calculer(
                        $dansBordeaux,
                        $distanceMetres ?? 0
                    );
                }

                $montantMenu = $calculPrixCommande
                    ->calculerMontantMenu($menu, $nombrePersonnes);

                $remise = $calculPrixCommande
                    ->calculerRemise($menu, $nombrePersonnes);

                $total = $calculPrixCommande->calculerTotal(
                    $menu,
                    $nombrePersonnes,
                    $fraisLivraison
                );

                foreach (
                    [$montantMenu, $remise, $fraisLivraison, $total]
                    as $montant
                ) {
                    if ($montant < 0 || $montant > 2147483647) {
                        throw new \DomainException(
                            'Un montant dépasse les limites autorisées.'
                        );
                    }
                }

                $recapitulatif = [
                    'adresse' => sprintf(
                        '%s, %s %s',
                        $donnees['adresse_livraison'],
                        $donnees['code_postal_livraison'],
                        $donnees['ville_livraison']
                    ),
                    'nombre_personnes' => $nombrePersonnes,
                    'date_prestation' => $donnees['date_prestation'],
                    'heure_livraison' => $donnees['heure_livraison'],
                    'adresse_identique' => $adresseIdentique,
                    'dans_bordeaux' => $dansBordeaux,
                    'distance_metres' => $distanceMetres,
                    'montant_menu' => $montantMenu,
                    'remise' => $remise,
                    'frais_livraison' => $fraisLivraison,
                    'total' => $total,
                ];

                $confirmation = $brouillon;
                unset($confirmation['adresses']);

                $confirmation['jeton_brouillon'] = $brouillon['jeton'];
                $confirmation['jeton'] = bin2hex(random_bytes(16));
                $confirmation['donnees'] = $donnees;
                $confirmation['recapitulatif'] = $recapitulatif;

                $session->set(
                    'modification_commande_equipe_confirmation',
                    $confirmation
                );
            }
        } catch (\DomainException $exception) {
            $erreur = $exception->getMessage();
        } catch (\RuntimeException | \InvalidArgumentException $exception) {
            $erreur = 'Impossible de préparer le récapitulatif. Vérifiez les informations ou réessayez plus tard.';
        }

        return $this->render(
            'gestion/commande/recapitulatif_modification.html.twig',
            [
                'commande' => $commande,
                'brouillon' => $brouillon,
                'adresseForm' => $form?->createView(),
                'recapitulatif' => $recapitulatif,
                'erreur' => $erreur,
                'jeton_confirmation' => $session->get(
                'modification_commande_equipe_confirmation'
                )['jeton'] ?? null,
            ]
        );
    }

    private function adresseIdentique(
        Commande $commande,
        array $donnees
    ): bool {
        return
            trim($donnees['adresse_livraison'])
                === trim($commande->getAdresseLivraison())
            && trim($donnees['code_postal_livraison'])
                === trim($commande->getCodePostalLivraison())
            && trim($donnees['ville_livraison'])
                === trim($commande->getVilleLivraison());
    }
}