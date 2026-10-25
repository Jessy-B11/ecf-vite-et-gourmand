<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\CommandeType;
use App\Form\ConfirmationAdresseType;
use App\Repository\MenuRepository;
use App\Service\GeocodageAdresse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CalculFraisLivraison;
use App\Service\CalculPrixCommande;
use App\Service\ItineraireLivraison;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\CommandeRepository;
use App\Service\EnregistrementCommande;
use App\Service\EmailConfirmationCommande;

final class CommandeController extends AbstractController
{
        public function __construct(
        #[Autowire(env: 'LIVRAISON_COMMUNE_GRATUITE')]
        private string $communeGratuite
    ) {
    }
    #[Route(
        '/menus/{id}/commander',
        name: 'app_commande',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        int $id,
        Request $request,
        MenuRepository $menuRepository,
        GeocodageAdresse $geocodageAdresse
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $menu = $menuRepository->find($id);

        if ($menu === null) {
            throw $this->createNotFoundException(
                'Ce menu n’existe pas.'
            );
        }

        if ($menu->getStockCommandes() <= 0) {
            $this->addFlash(
                'warning',
                'Ce menu est actuellement indisponible.'
            );

            return $this->redirectToRoute('app_menu_show', [
                'id' => $menu->getId(),
            ]);
        }

        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setNombrePersonnes($menu->getMinimumPersonnes());
        $commande->setAdresseLivraison(
            $utilisateur->getAdresse() ?? ''
        );
        $commande->setCodePostalLivraison(
            $utilisateur->getCodePostal() ?? ''
        );
        $commande->setVilleLivraison(
            $utilisateur->getVille() ?? ''
        );
        $commande->setTelephoneContact(
            $utilisateur->getTelephone() ?? ''
        );

        $dateMinimum = new \DateTimeImmutable(
            'today',
            new \DateTimeZone('Europe/Paris')
        );

        $dateMinimum = $dateMinimum->modify(
            sprintf('+%d days', $menu->getDelaiMinimumJours())
        );

        $form = $this->createForm(CommandeType::class, $commande, [
            'minimum_personnes' => $menu->getMinimumPersonnes(),
            'date_minimum' => $dateMinimum,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adresseComplete = sprintf(
                '%s %s %s',
                $commande->getAdresseLivraison(),
                $commande->getCodePostalLivraison(),
                $commande->getVilleLivraison()
            );

            try {
                $adressesProposees = $geocodageAdresse->rechercher(
                    $adresseComplete
                );

                if ($adressesProposees === []) {
                    $form->get('adresseLivraison')->addError(
                        new FormError(
                            'Aucune adresse précise trouvée. Vérifiez le numéro, la rue, le code postal et la ville.'
                        )
                    );
                } else {
                    $jeton = bin2hex(random_bytes(16));

                    $request->getSession()->set(
                        'commande_brouillon',
                        [
                            'jeton' => $jeton,
                            'utilisateur_id' => $utilisateur->getId(),
                            'menu_id' => $menu->getId(),
                            'nombre_personnes' => $commande->getNombrePersonnes(),
                            'date_prestation' => $commande
                                ->getDatePrestation()
                                ->format('Y-m-d'),
                            'heure_livraison' => $commande
                                ->getHeureLivraison()
                                ->format('H:i'),
                            'telephone_contact' => $commande->getTelephoneContact(),
                            'adresses' => $adressesProposees,
                            'expire_a' => time() + 1800,
                        ]
                    );

                    return $this->redirectToRoute(
                        'app_commande_adresse',
                        ['jeton' => $jeton],
                        Response::HTTP_SEE_OTHER
                    );
                }
            } catch (\RuntimeException $exception) {
                $form->addError(
                    new FormError(
                        'La recherche d’adresse est momentanément indisponible. Réessayez dans quelques instants.'
                    )
                );
            }
        }

        return $this->render('commande/index.html.twig', [
            'commandeForm' => $form->createView(),
            'menu' => $menu,
            'client' => $utilisateur,
            'recapitulatif' => null,
            'adresses_proposees' => [],
        ]);
    }

        #[Route(
        '/commande/adresse/{jeton}',
        name: 'app_commande_adresse',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function adresse(
        string $jeton,
        Request $request,
        MenuRepository $menuRepository,
        ItineraireLivraison $itineraireLivraison,
        CalculFraisLivraison $calculFraisLivraison,
        CalculPrixCommande $calculPrixCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $brouillon = $request->getSession()->get(
            'commande_brouillon'
        );

        if (
            !is_array($brouillon)
            || $brouillon['utilisateur_id'] !== $utilisateur->getId()
            || !hash_equals($brouillon['jeton'], $jeton)
            || $brouillon['expire_a'] <= time()
        ) {
            throw $this->createNotFoundException(
                'Ce brouillon est indisponible ou a expiré. Recommencez votre commande depuis le menu.'
            );
        }

        $menu = $menuRepository->find($brouillon['menu_id']);

        if ($menu === null) {
            throw $this->createNotFoundException(
                'Ce menu n’existe plus.'
            );
        }

        $form = $this->createForm(
            ConfirmationAdresseType::class,
            null,
            ['adresses' => $brouillon['adresses']]
        );

        $form->handleRequest($request);

        $recapitulatif = null;

        if ($form->isSubmitted()) {
            $request->getSession()->remove('commande_confirmation');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dateMinimum = new \DateTimeImmutable(
                'today',
                new \DateTimeZone('Europe/Paris')
            );

            $dateMinimum = $dateMinimum->modify(
                sprintf('+%d days', $menu->getDelaiMinimumJours())
            );

            if ($menu->getStockCommandes() <= 0) {
                $form->addError(
                    new FormError('Ce menu est désormais indisponible.')
                );
            }

            if (
                $brouillon['nombre_personnes']
                < $menu->getMinimumPersonnes()
            ) {
                $form->addError(
                    new FormError(
                        'Le minimum du menu a changé. Recommencez la saisie.'
                    )
                );
            }

            if (
                $brouillon['date_prestation']
                < $dateMinimum->format('Y-m-d')
            ) {
                $form->addError(
                    new FormError(
                        'La date ne respecte plus le délai du menu. Recommencez la saisie.'
                    )
                );
            }

            if ($form->isValid()) {
                $index = $form->get('adresse')->getData();

                $adresse = $brouillon['adresses'][$index];
                $proprietes = $adresse['properties'];
                $coordonnees = $adresse['geometry']['coordinates'] ?? null;
                $codeCommune = $proprietes['citycode'] ?? null;

                try {
                    if (
                        !is_string($codeCommune)
                        || $codeCommune === ''
                        || !is_array($coordonnees)
                        || count($coordonnees) !== 2
                        || !is_numeric($coordonnees[0])
                        || !is_numeric($coordonnees[1])
                    ) {
                        throw new \RuntimeException(
                            'Les données de cette adresse sont incomplètes.'
                        );
                    }

                    $dansBordeaux = $codeCommune === $this->communeGratuite;
                    $distanceMetres = null;

                    if (!$dansBordeaux) {
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
                        'nombre_personnes' => $nombrePersonnes,
                        'date_prestation' => $brouillon['date_prestation'],
                        'heure_livraison' => $brouillon['heure_livraison'],
                        'dans_bordeaux' => $dansBordeaux,
                        'distance_metres' => $distanceMetres,
                        'montant_menu' => $montantMenu,
                        'remise' => $remise,
                        'frais_livraison' => $fraisLivraison,
                        'total' => $total,
                    ];
                                        $request->getSession()->set(
                        'commande_confirmation',
                        [
                            'jeton_confirmation' => bin2hex(random_bytes(16)),
                            'jeton_brouillon' => $jeton,
                            'utilisateur_id' => $utilisateur->getId(),
                            'menu_id' => $menu->getId(),
                            'nombre_personnes' => $nombrePersonnes,
                            'date_prestation' => $brouillon['date_prestation'],
                            'heure_livraison' => $brouillon['heure_livraison'],
                            'telephone_contact' => $brouillon['telephone_contact'],
                            'adresse_livraison' => $proprietes['name'] ?? '',
                            'code_postal_livraison' => $proprietes['postcode'] ?? '',
                            'ville_livraison' => $proprietes['city'] ?? '',
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
                            'Impossible de calculer la livraison pour cette adresse. Vérifiez votre saisie ou réessayez plus tard.'
                        )
                    );
                }
            }
        }

            return $this->render('commande/adresse.html.twig', [
                'confirmationForm' => $form->createView(),
                'menu' => $menu,
                'recapitulatif' => $recapitulatif,
                'jeton_confirmation' => $request->getSession()
                    ->get('commande_confirmation')['jeton_confirmation'] ?? null,
        ]);
    }
            #[Route(
        '/commande/confirmer/{jeton}',
        name: 'app_commande_confirmation',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
        public function confirmation(
        string $jeton,
        Request $request,
        MenuRepository $menuRepository,
        EnregistrementCommande $enregistrementCommande,
        EmailConfirmationCommande $emailConfirmationCommande,
        CalculFraisLivraison $calculFraisLivraison
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();
        $confirmation = $session->get('commande_confirmation');
        $brouillon = $session->get('commande_brouillon');

        if (
            !is_array($confirmation)
            || !is_array($brouillon)
            || $confirmation['utilisateur_id'] !== $utilisateur->getId()
            || $brouillon['utilisateur_id'] !== $utilisateur->getId()
            || !hash_equals(
                $confirmation['jeton_confirmation'],
                $jeton
            )
            || !hash_equals(
                $brouillon['jeton'],
                $confirmation['jeton_brouillon']
            )
            || $confirmation['menu_id'] !== $brouillon['menu_id']
        ) {
            throw $this->createNotFoundException(
                'Cette confirmation est indisponible. Recommencez depuis le menu.'
            );
        }

        // Une validation déjà réussie affiche son résultat.
        if (isset($confirmation['commande_id'])) {
            return $this->redirectToRoute(
                'app_commande_succes',
                ['id' => $confirmation['commande_id']],
                Response::HTTP_SEE_OTHER
            );
        }

        if (
            $confirmation['expire_a'] <= time()
            || $brouillon['expire_a'] <= time()
        ) {
            throw $this->createNotFoundException(
                'Cette confirmation a expiré. Recommencez depuis le menu.'
            );
        }

        $menu = $menuRepository->find($confirmation['menu_id']);

        if ($menu === null) {
            throw $this->createNotFoundException(
                'Ce menu n’existe plus.'
            );
        }

        if ($request->isMethod('POST')) {
            $jetonCsrf = $request->request->getString('_token');

            if (!$this->isCsrfTokenValid(
                'confirmer_commande_' . $jeton,
                $jetonCsrf
            )) {
                throw $this->createAccessDeniedException(
                    'La validation du formulaire est invalide. Rechargez la page.'
                );
            }

            // Ces données viennent de la session du serveur,
            // pas de champs de prix envoyés par le navigateur.
            $recapitulatif = $confirmation['recapitulatif'];
            $fuseau = new \DateTimeZone('Europe/Paris');

            $commande = new Commande();
            $commande->setUtilisateur($utilisateur);
            $commande->setMenu($menu);
            $commande->setJetonValidation(
                $confirmation['jeton_brouillon']
            );
            $commande->setNombrePersonnes(
                $confirmation['nombre_personnes']
            );
            $commande->setDatePrestation(
                new \DateTimeImmutable(
                    $confirmation['date_prestation'],
                    $fuseau
                )
            );
            $commande->setHeureLivraison(
                new \DateTimeImmutable(
                    '1970-01-01 ' . $confirmation['heure_livraison'],
                    $fuseau
                )
            );
            $commande->setAdresseLivraison(
                $confirmation['adresse_livraison']
            );
            $commande->setCodePostalLivraison(
                $confirmation['code_postal_livraison']
            );
            $commande->setVilleLivraison(
                $confirmation['ville_livraison']
            );
            $commande->setTelephoneContact(
                $confirmation['telephone_contact']
            );
            $commande->setMontantMenuCentimes(
                $recapitulatif['montant_menu']
            );
            $commande->setMontantRemiseCentimes(
                $recapitulatif['remise']
            );
            $commande->setFraisLivraisonCentimes(
                $recapitulatif['frais_livraison']
            );
            $commande->setMontantTotalCentimes(
                $recapitulatif['total']
            );

                        try {
                $calculFraisLivraison->verifierZone(
                    $recapitulatif['dans_bordeaux'],
                    $recapitulatif['distance_metres'] ?? 0
                );

                $commandeEnregistree = $enregistrementCommande
                    ->enregistrer($commande);
            } catch (\DomainException | \InvalidArgumentException $exception) {
                $session->remove('commande_confirmation');

                $this->addFlash(
                    'warning',
                    $exception->getMessage()
                );

                return $this->redirectToRoute(
                    'app_menu_show',
                    ['id' => $menu->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }

            $confirmation['commande_id'] = $commandeEnregistree->getId();

            $session->set(
                'commande_confirmation',
                $confirmation
            );
                        if ($commandeEnregistree === $commande) {
                $emailConfirmationCommande->envoyer(
                    $commandeEnregistree
                );
            }

            return $this->redirectToRoute(
                'app_commande_succes',
                ['id' => $commandeEnregistree->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('commande/confirmation.html.twig', [
            'menu' => $menu,
            'confirmation' => $confirmation,
            'recapitulatif' => $confirmation['recapitulatif'],
        ]);
    }
        #[Route(
        '/commande/succes/{id}',
        name: 'app_commande_succes',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function succes(
        int $id,
        CommandeRepository $commandeRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $commande = $commandeRepository->find($id);

        if (
            $commande === null
            || $commande->getUtilisateur()?->getId()
                !== $utilisateur->getId()
        ) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        return $this->render('commande/succes.html.twig', [
            'commande' => $commande,
        ]);
    }
}