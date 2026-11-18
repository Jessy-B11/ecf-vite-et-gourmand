<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommandeRepository;
use App\Service\ModificationCommande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ConfirmationModificationCommandeController extends AbstractController
{
    #[Route(
        '/mes-commandes/modification/confirmer/{jeton}',
        name: 'app_modification_commande_confirmation',
        requirements: ['jeton' => '[a-f0-9]{32}'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        string $jeton,
        Request $request,
        CommandeRepository $commandeRepository,
        ModificationCommande $modificationCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();
        $confirmation = $session->get(
            'modification_commande_confirmation'
        );

        if (
            !is_array($confirmation)
            || $confirmation['utilisateur_id'] !== $utilisateur->getId()
            || !hash_equals($confirmation['jeton'], $jeton)
        ) {
            throw $this->createNotFoundException(
                'Cette confirmation est indisponible.'
            );
        }

        $id = $confirmation['commande_id'];

        if ($confirmation['enregistree'] ?? false) {
            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        if ($confirmation['expire_a'] <= time()) {
            throw $this->createNotFoundException(
                'Cette confirmation a expiré. Recommencez la modification.'
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

        if ($commande->getStatut() !== 'en_attente') {
            $this->addFlash(
                'warning',
                'Cette commande ne peut plus être modifiée en ligne.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        if (!hash_equals(
            $confirmation['empreinte_source'],
            $modificationCommande->calculerEmpreinte($commande)
        )) {
            $session->remove('modification_commande_confirmation');

            $this->addFlash(
                'warning',
                'La commande a changé. Recommencez la modification depuis son détail.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'modifier_commande_' . $jeton,
                $request->request->getString('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Le formulaire est invalide. Rechargez la page.'
                );
            }

            try {
                $modifiee = $modificationCommande->modifier(
                    $commande,
                    $utilisateur,
                    $confirmation
                );
            } catch (\DomainException | \InvalidArgumentException $exception) {
                $session->remove(
                    'modification_commande_confirmation'
                );

                $this->addFlash(
                    'warning',
                    $exception->getMessage()
                );

                return $this->redirectToRoute(
                    'app_mes_commandes_show',
                    ['id' => $id],
                    Response::HTTP_SEE_OTHER
                );
            }

            $confirmation['enregistree'] = true;

            $session->set(
                'modification_commande_confirmation',
                $confirmation
            );

            $session->remove('modification_commande_adresse');

            $this->addFlash(
                'success',
                $modifiee
                    ? 'Votre commande a bien été modifiée.'
                    : 'Les informations sont identiques. Aucun changement nécessaire.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes_show',
                ['id' => $id],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('commande/confirmation_modification.html.twig', [
            'commande' => $commande,
            'confirmation' => $confirmation,
            'recapitulatif' => $confirmation['recapitulatif'],
        ]);
    }
}