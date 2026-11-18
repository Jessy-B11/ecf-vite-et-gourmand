<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\HistoriqueCommandeRepository;
use App\Service\AnnulationCommande;
use Symfony\Component\HttpFoundation\Request;

final class MesCommandesController extends AbstractController
{
    #[Route(
        '/mes-commandes',
        name: 'app_mes_commandes',
        methods: ['GET']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        CommandeRepository $commandeRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $commandes = $commandeRepository->findBy(
            ['utilisateur' => $utilisateur],
            [
                'dateCreation' => 'DESC',
                'id' => 'DESC',
            ]
        );

        return $this->render('commande/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }
        #[Route(
        '/mes-commandes/{id}',
        name: 'app_mes_commandes_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function show(
        int $id,
        CommandeRepository $commandeRepository,
        HistoriqueCommandeRepository $historiqueRepository
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

        $historique = $historiqueRepository->findBy(
            ['commande' => $commande],
            [
                'dateChangement' => 'ASC',
                'id' => 'ASC',
            ]
        );

        return $this->render('commande/detail.html.twig', [
            'commande' => $commande,
            'historique' => $historique,
        ]);
    }
        #[Route(
        '/mes-commandes/{id}/annuler',
        name: 'app_mes_commandes_annuler',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function annuler(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        AnnulationCommande $annulationCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'annuler_commande_' . $id,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Rechargez la page.'
            );
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

        try {
            $annulee = $annulationCommande->annuler(
                $commande,
                $utilisateur
            );

            if ($annulee) {
                $this->addFlash(
                    'success',
                    'Votre commande a bien été annulée.'
                );
            } else {
                $this->addFlash(
                    'warning',
                    'Cette commande était déjà annulée.'
                );
            }
        } catch (\DomainException $exception) {
            $this->addFlash(
                'warning',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute(
            'app_mes_commandes_show',
            ['id' => $id],
            Response::HTTP_SEE_OTHER
        );
    }
}