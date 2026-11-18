<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\AnnulationCommandeEquipeType;
use App\Repository\CommandeRepository;
use App\Service\AnnulationCommandeEquipe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
final class AnnulationCommandeEquipeController extends AbstractController
{
    #[Route(
        '/gestion/commandes/{id}/annuler',
        name: 'app_gestion_commandes_annuler',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function index(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        AnnulationCommandeEquipe $annulationCommandeEquipe
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
                $commande->getStatut() === 'annulee'
                    ? 'Cette commande est déjà annulée.'
                    : 'Cette commande ne peut plus être annulée depuis cet écran.'
            );

            return $this->redirectToRoute(
                'app_gestion_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        $form = $this->createForm(
            AnnulationCommandeEquipeType::class,
            null,
            [
                'csrf_token_id' => 'annulation_commande_equipe_' . $id,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donnees = $form->getData();

            try {
                $annulee = $annulationCommandeEquipe->annuler(
                    $commande,
                    $auteur,
                    $donnees['modeContact'],
                    $donnees['motif'],
                    $donnees['clientContacte']
                );
            } catch (\DomainException | \InvalidArgumentException $exception) {
                $this->addFlash(
                    'warning',
                    $exception->getMessage()
                );

                return $this->redirectToRoute(
                    'app_gestion_commandes_show',
                    ['id' => $id],
                    Response::HTTP_SEE_OTHER
                );
            }

            $this->addFlash(
                'success',
                $annulee
                    ? 'La commande a été annulée et une unité de stock a été restituée.'
                    : 'Cette commande est déjà annulée.'
            );

            return $this->redirectToRoute(
                'app_gestion_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('gestion/commande/annuler.html.twig', [
            'commande' => $commande,
            'annulationForm' => $form->createView(),
        ]);
    }
}