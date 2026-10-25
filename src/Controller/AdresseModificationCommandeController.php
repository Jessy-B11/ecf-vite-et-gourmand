<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ConfirmationAdresseType;
use App\Repository\CommandeRepository;
use App\Service\CalculFraisLivraison;
use App\Service\CalculPrixCommande;
use App\Service\ItineraireLivraison;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ModificationCommande;

final class AdresseModificationCommandeController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'LIVRAISON_COMMUNE_GRATUITE')]
        private string $communeGratuite
    ) {
    }

    #[Route(
        '/mes-commandes/modification/adresse/{jeton}',
        name: 'app_modification_commande_adresse',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        string $jeton,
        Request $request,
        CommandeRepository $commandeRepository,
        ItineraireLivraison $itineraireLivraison,
        CalculFraisLivraison $calculFraisLivraison,
        CalculPrixCommande $calculPrixCommande,
        ModificationCommande $modificationCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $brouillon = $request->getSession()->get(
            'modification_commande_adresse'
        );

        if (
            !is_array($brouillon)
            || $brouillon['utilisateur_id'] !== $utilisateur->getId()
            || !hash_equals($brouillon['jeton'], $jeton)
            || $brouillon['expire_a'] <= time()
        ) {
            throw $this->createNotFoundException(
                'Cette demande de modification est indisponible ou a expiré.'
            );
        }

        $commande = $commandeRepository->findOneBy([
            'id' => $brouillon['commande_id'],
            'utilisateur' => $utilisateur,
        ]);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        if ($commande->getStatut() !== 'en_attente') {
            $this->addFlash(
                'warning',
                'Cette commande ne peut plus être modifiée en ligne.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $commande->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        $menu = $commande->getMenu();

        if (
            $menu === null
            || $menu->getId() !== $brouillon['menu_id']
        ) {
            throw $this->createNotFoundException(
                'Le menu de cette demande ne correspond plus à la commande.'
            );
        }

        $form = $this->createForm(
            ConfirmationAdresseType::class,
            null,
            [
                'adresses' => $brouillon['adresses'],
                'csrf_token_id' => 'modifier_adresse_' . $jeton,
            ]
        );

        $form->handleRequest($request);
                if ($form->isSubmitted()) {
            $request->getSession()->remove(
                'modification_commande_confirmation'
            );
        }
        $recapitulatif = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $index = $form->get('adresse')->getData();
            $adresse = $brouillon['adresses'][$index];
            $proprietes = $adresse['properties'];
            $coordonnees = $adresse['geometry']['coordinates'] ?? null;

            try {
                                if (!hash_equals(
                    $brouillon['empreinte_source'],
                    $modificationCommande->calculerEmpreinte($commande)
                )) {
                    throw new \DomainException(
                        'La commande a changé. Recommencez la modification depuis son détail.'
                    );
                }
                foreach (['name', 'postcode', 'city', 'citycode'] as $champ) {
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

                $dateMinimum = new \DateTimeImmutable(
                    'today',
                    new \DateTimeZone('Europe/Paris')
                );

                $dateMinimum = $dateMinimum->modify(
                    sprintf('+%d days', $menu->getDelaiMinimumJours())
                );

                if (
                    $brouillon['date_prestation']
                    < $dateMinimum->format('Y-m-d')
                ) {
                    throw new \DomainException(
                        'La date ne respecte plus le délai du menu. Recommencez la modification.'
                    );
                }

                $adresseIdentique =
                    trim($proprietes['name'])
                        === trim($commande->getAdresseLivraison())
                    && trim($proprietes['postcode'])
                        === trim($commande->getCodePostalLivraison())
                    && trim($proprietes['city'])
                        === trim($commande->getVilleLivraison());

                $distanceMetres = null;
                $dansBordeaux = $proprietes['citycode']
                    === $this->communeGratuite;

                if ($adresseIdentique) {
                    $fraisLivraison = $commande
                        ->getFraisLivraisonCentimes();
                } else {
                    if (!$dansBordeaux) {
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

                        $distanceMetres = $itineraireLivraison
                            ->calculerDistance(
                                (float) $coordonnees[0],
                                (float) $coordonnees[1]
                            );
                    }

                    $fraisLivraison = $calculFraisLivraison->calculer(
                        $dansBordeaux,
                        $distanceMetres ?? 0
                    );
                }

                $nombrePersonnes = $brouillon['nombre_personnes'];

                $montantMenu = $calculPrixCommande
                    ->calculerMontantMenu($menu, $nombrePersonnes);

                $remise = $calculPrixCommande
                    ->calculerRemise($menu, $nombrePersonnes);

                $total = $calculPrixCommande->calculerTotal(
                    $menu,
                    $nombrePersonnes,
                    $fraisLivraison
                );

                $recapitulatif = [
                    'adresse' => $proprietes['label'],
                    'dans_bordeaux' => $dansBordeaux,
                    'nombre_personnes' => $nombrePersonnes,
                    'date_prestation' => $brouillon['date_prestation'],
                    'heure_livraison' => $brouillon['heure_livraison'],
                    'adresse_identique' => $adresseIdentique,
                    'distance_metres' => $distanceMetres,
                    'montant_menu' => $montantMenu,
                    'remise' => $remise,
                    'frais_livraison' => $fraisLivraison,
                    'total' => $total,
                ];

                                $request->getSession()->set(
                    'modification_commande_confirmation',
                    [
                        'jeton' => bin2hex(random_bytes(16)),
                        'commande_id' => $commande->getId(),
                        'utilisateur_id' => $utilisateur->getId(),
                        'menu_id' => $menu->getId(),
                        'empreinte_source' => $brouillon['empreinte_source'],
                        'donnees' => [
                            'nombre_personnes' => $nombrePersonnes,
                            'date_prestation' => $brouillon['date_prestation'],
                            'heure_livraison' => $brouillon['heure_livraison'],
                            'adresse_livraison' => $proprietes['name'],
                            'code_postal_livraison' => $proprietes['postcode'],
                            'ville_livraison' => $proprietes['city'],
                            'telephone_contact' => $brouillon['telephone_contact'],
                        ],
                        'recapitulatif' => $recapitulatif,
                        'expire_a' => $brouillon['expire_a'],
                    ]
                );
            } catch (\DomainException $exception) {
                $form->addError(
                    new FormError($exception->getMessage())
                );
            } catch (\RuntimeException | \InvalidArgumentException $exception) {
                $form->addError(
                    new FormError(
                        'Impossible de préparer ce récapitulatif. Vérifiez les informations ou réessayez plus tard.'
                    )
                );
            }
        }

        return $this->render('commande/modification_adresse.html.twig', [
            'commande' => $commande,
            'confirmationForm' => $form->createView(),
            'recapitulatif' => $recapitulatif,
            'jeton_confirmation' => $request->getSession()
                ->get('modification_commande_confirmation')['jeton'] ?? null,
        ]);
    }
}