<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Service\ModificationCommandeEquipe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
final class ConfirmationModificationCommandeEquipeController extends AbstractController
{
    #[Route(
        '/gestion/commandes/modification/confirmer/{jeton}',
        name: 'app_gestion_modification_commande_confirmer',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['POST']
    )]
    public function index(
        string $jeton,
        Request $request,
        CommandeRepository $commandeRepository,
        ModificationCommandeEquipe $modificationCommandeEquipe
    ): Response {
        $auteur = $this->getUser();

        if (!$auteur instanceof Utilisateur || !$auteur->isActif()) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();
        $cleBrouillon = 'modification_commande_equipe_brouillon';
        $cleConfirmation = 'modification_commande_equipe_confirmation';

        $brouillon = $session->get($cleBrouillon);
        $confirmation = $session->get($cleConfirmation);

        if (
            !is_array($brouillon)
            || !is_array($confirmation)
            || $confirmation['auteur_id'] !== $auteur->getId()
            || $brouillon['auteur_id'] !== $auteur->getId()
            || $confirmation['commande_id'] !== $brouillon['commande_id']
            || !hash_equals($confirmation['jeton'], $jeton)
            || !hash_equals(
                $confirmation['jeton_brouillon'],
                $brouillon['jeton']
            )
            || $confirmation['expire_a'] <= time()
            || $brouillon['expire_a'] <= time()
        ) {
            throw $this->createNotFoundException(
                'Cette confirmation a expiré ou a déjà été utilisée.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'confirmer_modification_equipe_' . $jeton,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Recommencez la modification.'
            );
        }

        $commande = $commandeRepository->find(
            $confirmation['commande_id']
        );

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        $id = $commande->getId();

        // Chaque confirmation est utilisable une seule fois.
        $session->remove($cleConfirmation);
        $session->remove($cleBrouillon);

        try {
            $modifiee = $modificationCommandeEquipe->modifier(
                $commande,
                $auteur,
                $confirmation
            );

            $this->addFlash(
                'success',
                $modifiee
                    ? 'La commande a été modifiée.'
                    : 'Les informations de la commande étaient déjà identiques.'
            );
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute(
            'app_gestion_commandes_show',
            ['id' => $id],
            Response::HTTP_SEE_OTHER
        );
    }
}