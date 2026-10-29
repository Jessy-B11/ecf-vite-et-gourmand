<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Service\AcceptationCommande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\HistoriqueCommandeRepository;
use App\Service\SuiviCommande;
use App\Service\PretMaterielCommande;

#[IsGranted('ROLE_EMPLOYE')]
final class GestionCommandeController extends AbstractController
{
    #[Route(
        '/gestion/commandes',
        name: 'app_gestion_commandes',
        methods: ['GET']
    )]
    public function index(
        CommandeRepository $commandeRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof Utilisateur
            || !$utilisateur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('gestion/commande/index.html.twig', [
            'commandes' => $commandeRepository
                ->findDernieresPourGestion(),
        ]);
    }

        #[Route(
        '/gestion/commandes/{id}',
        name: 'app_gestion_commandes_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        CommandeRepository $commandeRepository,
        HistoriqueCommandeRepository $historiqueCommandeRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof Utilisateur
            || !$utilisateur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        $commande = $commandeRepository->find($id);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        $historique = $historiqueCommandeRepository->findBy(
            ['commande' => $commande],
            ['dateChangement' => 'ASC', 'id' => 'ASC']
        );

        return $this->render('gestion/commande/detail.html.twig', [
            'commande' => $commande,
            'historique' => $historique,
        ]);
    }

    #[Route(
        '/gestion/commandes/{id}/accepter',
        name: 'app_gestion_commandes_accepter',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function accepter(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        AcceptationCommande $acceptationCommande
    ): Response {
        $auteur = $this->getUser();

        if (
            !$auteur instanceof Utilisateur
            || !$auteur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'accepter_commande_' . $id,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Rechargez la page.'
            );
        }

        $commande = $commandeRepository->find($id);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        try {
            $acceptee = $acceptationCommande->accepter(
                $commande,
                $auteur
            );
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->addFlash(
                'warning',
                $exception->getMessage()
            );

            return $this->redirectToRoute(
                'app_gestion_commandes',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $this->addFlash(
            'success',
            $acceptee
                ? 'La commande a bien été acceptée.'
                : 'Cette commande est déjà acceptée.'
        );

        return $this->redirectToRoute(
            'app_gestion_commandes',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
        #[Route(
        '/gestion/commandes/{id}/suivi/{statut}',
        name: 'app_gestion_commandes_suivi',
        requirements: [
            'id' => '\d+',
                'statut' => 'en_preparation|en_livraison|livree|terminee',
        ],
        methods: ['POST']
    )]
    public function suivi(
        int $id,
        string $statut,
        Request $request,
        CommandeRepository $commandeRepository,
        SuiviCommande $suiviCommande
    ): Response {
        $auteur = $this->getUser();

        if (
            !$auteur instanceof Utilisateur
            || !$auteur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'suivi_commande_' . $id . '_' . $statut,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Rechargez la page.'
            );
        }

        $commande = $commandeRepository->find($id);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        try {
            $modifiee = $suiviCommande->changerStatut(
                $commande,
                $auteur,
                $statut
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
            $modifiee
                ? 'Le statut de la commande a bien été mis à jour.'
                : 'La commande possède déjà ce statut.'
        );

        return $this->redirectToRoute(
            'app_gestion_commandes_show',
            ['id' => $id],
            Response::HTTP_SEE_OTHER
        );
    }
        #[Route(
        '/gestion/commandes/{id}/materiel/{pret}',
        name: 'app_gestion_commandes_materiel',
        requirements: [
            'id' => '\d+',
            'pret' => '0|1',
        ],
        methods: ['POST']
    )]
    public function materiel(
        int $id,
        string $pret,
        Request $request,
        CommandeRepository $commandeRepository,
        PretMaterielCommande $pretMaterielCommande
    ): Response {
        $auteur = $this->getUser();

        if (
            !$auteur instanceof Utilisateur
            || !$auteur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'materiel_commande_' . $id . '_' . $pret,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Rechargez la page.'
            );
        }

        $commande = $commandeRepository->find($id);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        try {
            $modifiee = $pretMaterielCommande->definir(
                $commande,
                $auteur,
                $pret === '1'
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
            $modifiee
                ? 'Le prêt de matériel a bien été mis à jour.'
                : 'Ce choix est déjà enregistré.'
        );

        return $this->redirectToRoute(
            'app_gestion_commandes_show',
            ['id' => $id],
            Response::HTTP_SEE_OTHER
        );
    }
}