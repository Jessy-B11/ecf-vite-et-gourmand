<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CalculPrixCommande;
use Symfony\Component\Form\FormError;
use App\Service\GeocodageAdresse;
use App\Service\ModificationCommande;

final class ModificationCommandeController extends AbstractController
{
    #[Route(
        '/mes-commandes/{id}/modifier',
        name: 'app_mes_commandes_modifier',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        CalculPrixCommande $calculPrixCommande,
        GeocodageAdresse $geocodageAdresse,
        ModificationCommande $modificationCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $commande = $commandeRepository->findOneBy([
            'id' => $id,
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
                'Cette commande ne peut plus être modifiée en ligne. Contactez notre équipe.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        $menu = $commande->getMenu();

        if ($menu === null) {
            throw $this->createNotFoundException(
                'Le menu de cette commande est introuvable.'
            );
        }

        // Objet temporaire de saisie :
        // ne pas modifier directement la commande suivie par Doctrine.
        $saisie = new Commande();
        $saisie->setNombrePersonnes(
            $commande->getNombrePersonnes()
        );
        $saisie->setDatePrestation(
            new \DateTimeImmutable(
                $commande->getDatePrestation()->format('Y-m-d'),
                new \DateTimeZone('Europe/Paris')
            )
        );
        $saisie->setHeureLivraison(
            $commande->getHeureLivraison()
        );
        $saisie->setAdresseLivraison(
            $commande->getAdresseLivraison()
        );
        $saisie->setCodePostalLivraison(
            $commande->getCodePostalLivraison()
        );
        $saisie->setVilleLivraison(
            $commande->getVilleLivraison()
        );
        $saisie->setTelephoneContact(
            $commande->getTelephoneContact()
        );

        $dateMinimum = new \DateTimeImmutable(
            'today',
            new \DateTimeZone('Europe/Paris')
        );

        $dateMinimum = $dateMinimum->modify(
            sprintf('+%d days', $menu->getDelaiMinimumJours())
        );

        $form = $this->createForm(CommandeType::class, $saisie, [
            'minimum_personnes' => $menu->getMinimumPersonnes(),
            'date_minimum' => $dateMinimum,
        ]);

        $form->handleRequest($request);
                if ($form->isSubmitted()) {
            $request->getSession()->remove(
                'modification_commande_confirmation'
            );

            $request->getSession()->remove(
                'modification_commande_adresse'
            );
        }

                $recapitulatif = null;
                $adressesProposees = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $adresseIdentique =
                trim($saisie->getAdresseLivraison())
                    === trim($commande->getAdresseLivraison())
                && trim($saisie->getCodePostalLivraison())
                    === trim($commande->getCodePostalLivraison())
                && trim($saisie->getVilleLivraison())
                    === trim($commande->getVilleLivraison());

            if (!$adresseIdentique) {
                $adresseComplete = sprintf(
                    '%s %s %s',
                    $saisie->getAdresseLivraison(),
                    $saisie->getCodePostalLivraison(),
                    $saisie->getVilleLivraison()
                );

                try {
                    $adressesProposees = $geocodageAdresse
                        ->rechercher($adresseComplete);

                    if ($adressesProposees === []) {
                        $form->get('adresseLivraison')->addError(
                            new FormError(
                                'Aucune adresse précise trouvée. Vérifiez le numéro, la rue, le code postal et la ville.'
                            )
                        );
                    } else {
                        $jeton = bin2hex(random_bytes(16));

                        $request->getSession()->set(
                            'modification_commande_adresse',
                            [
                                'jeton' => $jeton,
                                'utilisateur_id' => $utilisateur->getId(),
                                'commande_id' => $commande->getId(),
                                'menu_id' => $menu->getId(),
                                'empreinte_source' => $modificationCommande
                                    ->calculerEmpreinte($commande),
                                'nombre_personnes' => $saisie->getNombrePersonnes(),
                                'date_prestation' => $saisie
                                    ->getDatePrestation()
                                    ->format('Y-m-d'),
                                'heure_livraison' => $saisie
                                    ->getHeureLivraison()
                                    ->format('H:i'),
                                'telephone_contact' => $saisie->getTelephoneContact(),
                                'adresses' => $adressesProposees,
                                'expire_a' => time() + 1800,
                            ]
                        );

                        return $this->redirectToRoute(
                            'app_modification_commande_adresse',
                            ['jeton' => $jeton],
                            Response::HTTP_SEE_OTHER
                        );
                    }
                } catch (\RuntimeException $exception) {
                    $form->addError(
                        new FormError(
                            'La recherche d’adresse est momentanément indisponible. Réessayez plus tard.'
                        )
                    );
                }
            } else {
                try {
                    $nombrePersonnes = $saisie->getNombrePersonnes();

                    $montantMenu = $calculPrixCommande
                        ->calculerMontantMenu(
                            $menu,
                            $nombrePersonnes
                        );

                    $remise = $calculPrixCommande->calculerRemise(
                        $menu,
                        $nombrePersonnes
                    );

                    $fraisLivraison = $commande
                        ->getFraisLivraisonCentimes();

                    $total = $calculPrixCommande->calculerTotal(
                        $menu,
                        $nombrePersonnes,
                        $fraisLivraison
                    );

                    $recapitulatif = [
                        'nombre_personnes' => $nombrePersonnes,
                        'date_prestation' => $saisie
                            ->getDatePrestation()
                            ->format('Y-m-d'),
                        'heure_livraison' => $saisie
                            ->getHeureLivraison()
                            ->format('H:i'),
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
                            'empreinte_source' => $modificationCommande
                                ->calculerEmpreinte($commande),
                            'donnees' => [
                                'nombre_personnes' => $saisie->getNombrePersonnes(),
                                'date_prestation' => $saisie
                                    ->getDatePrestation()
                                    ->format('Y-m-d'),
                                'heure_livraison' => $saisie
                                    ->getHeureLivraison()
                                    ->format('H:i'),
                                'adresse_livraison' => $saisie->getAdresseLivraison(),
                                'code_postal_livraison' => $saisie->getCodePostalLivraison(),
                                'ville_livraison' => $saisie->getVilleLivraison(),
                                'telephone_contact' => $saisie->getTelephoneContact(),
                            ],
                            'recapitulatif' => $recapitulatif,
                            'expire_a' => time() + 1800,
                        ]
                    );
                } catch (\InvalidArgumentException $exception) {
                    $form->addError(
                        new FormError($exception->getMessage())
                    );
                }
            }
        }

        return $this->render('commande/modifier.html.twig', [
            'commande' => $commande,
            'menu' => $menu,
            'modificationForm' => $form->createView(),
            'recapitulatif' => $recapitulatif,
            'adresses_proposees' => $adressesProposees,
            'jeton_confirmation' => $request->getSession()
                ->get('modification_commande_confirmation')['jeton'] ?? null,
        ]);
    }
}