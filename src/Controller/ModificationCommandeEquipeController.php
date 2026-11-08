<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\ModificationCommandeEquipeType;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ModificationCommande;

#[IsGranted('ROLE_EMPLOYE')]
final class ModificationCommandeEquipeController extends AbstractController
{
    #[Route(
        '/gestion/commandes/{id}/modifier',
        name: 'app_gestion_commandes_modifier',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function index(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        ModificationCommande $modificationCommande
    ): Response {
        $auteur = $this->getUser();

        if (
            !$auteur instanceof Utilisateur
            || !$auteur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        $commande = $commandeRepository->find($id);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        if (!in_array(
            $commande->getStatut(),
            ['en_attente', 'acceptee', 'en_preparation'],
            true
        )) {
            $this->addFlash(
                'warning',
                'Cette commande ne peut plus être modifiée depuis cet écran.'
            );

            return $this->redirectToRoute(
                'app_gestion_commandes_show',
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

        $fuseau = new \DateTimeZone('Europe/Paris');

        $dateInitiale = new \DateTimeImmutable(
            $commande->getDatePrestation()->format('Y-m-d'),
            $fuseau
        );

        $saisie = (new Commande())
            ->setNombrePersonnes($commande->getNombrePersonnes())
            ->setDatePrestation($dateInitiale)
            ->setHeureLivraison($commande->getHeureLivraison())
            ->setAdresseLivraison($commande->getAdresseLivraison())
            ->setCodePostalLivraison($commande->getCodePostalLivraison())
            ->setVilleLivraison($commande->getVilleLivraison())
            ->setTelephoneContact($commande->getTelephoneContact());

        $aujourdhui = new \DateTimeImmutable('today', $fuseau);

        $dateMinimum = $aujourdhui->modify(
            sprintf('+%d days', $menu->getDelaiMinimumJours())
        );

        // Permettre de conserver une date existante proche.
        $dateMinimumFormulaire = $dateInitiale < $dateMinimum
            ? $dateInitiale
            : $dateMinimum;

        if ($dateMinimumFormulaire < $aujourdhui) {
            $dateMinimumFormulaire = $aujourdhui;
        }

        $form = $this->createForm(
            ModificationCommandeEquipeType::class,
            $saisie,
            [
                'minimum_personnes' => $menu->getMinimumPersonnes(),
                'date_minimum' => $dateMinimumFormulaire,
                'csrf_token_id' => 'modification_commande_equipe_' . $id,
            ]
        );

        $form->handleRequest($request);

                $session = $request->getSession();

        if ($form->isSubmitted()) {
            $session->remove(
                'modification_commande_equipe_brouillon'
            );

            $session->remove(
                'modification_commande_equipe_confirmation'
            );
        }

                if ($form->isSubmitted() && $form->isValid()) {
            $dateSaisie = $saisie->getDatePrestation();

            $dateChangee =
                $dateSaisie->format('Y-m-d')
                !== $dateInitiale->format('Y-m-d');

            if ($dateChangee && $dateSaisie < $dateMinimum) {
                $form->get('datePrestation')->addError(
                    new FormError(
                        'La nouvelle date ne respecte pas le délai du menu.'
                    )
                );
            }

            if ($form->isValid()) {
                $session->set(
                    'modification_commande_equipe_brouillon',
                    [
                        'jeton' => bin2hex(random_bytes(16)),
                        'commande_id' => $commande->getId(),
                        'menu_id' => $menu->getId(),
                        'utilisateur_id' => $commande
                            ->getUtilisateur()
                            ->getId(),
                        'auteur_id' => $auteur->getId(),
                        'empreinte_source' => $modificationCommande
                            ->calculerEmpreinte($commande),
                        'mode_contact' => $form
                            ->get('modeContact')
                            ->getData(),
                        'motif' => trim(
                            $form->get('motif')->getData()
                        ),
                        'client_contacte' => $form
                            ->get('clientContacte')
                            ->getData(),
                        'donnees' => [
                            'nombre_personnes' => $saisie
                                ->getNombrePersonnes(),
                            'date_prestation' => $saisie
                                ->getDatePrestation()
                                ->format('Y-m-d'),
                            'heure_livraison' => $saisie
                                ->getHeureLivraison()
                                ->format('H:i'),
                            'adresse_livraison' => $saisie
                                ->getAdresseLivraison(),
                            'code_postal_livraison' => $saisie
                                ->getCodePostalLivraison(),
                            'ville_livraison' => $saisie
                                ->getVilleLivraison(),
                            'telephone_contact' => $saisie
                                ->getTelephoneContact(),
                        ],
                                             'expire_a' => time() + 1800,
                    ]
                );

                return $this->redirectToRoute(
                    'app_gestion_modification_commande_recapitulatif',
                    [
                        'jeton' => $session->get(
                            'modification_commande_equipe_brouillon'
                        )['jeton'],
                    ],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->render('gestion/commande/modifier.html.twig', [
            'commande' => $commande,
            'modificationForm' => $form->createView(),
            'saisie_verifiee' => $form->isSubmitted() && $form->isValid(),
        ]);
    }
}