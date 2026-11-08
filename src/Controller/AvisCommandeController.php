<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use App\Form\AvisType;
use App\Repository\CommandeRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
final class AvisCommandeController extends AbstractController
{
    #[Route(
        '/mes-commandes/{id}/avis',
        name: 'app_commande_avis',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function deposer(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $client = $this->getUser();

        if (!$client instanceof Utilisateur || !$client->isActif()) {
            throw $this->createAccessDeniedException();
        }

        $commande = $commandeRepository->findOneBy([
            'id' => $id,
            'utilisateur' => $client,
        ]);

        if ($commande === null) {
            throw $this->createNotFoundException(
                'Cette commande est introuvable.'
            );
        }

        if ($commande->getStatut() !== 'terminee') {
            $this->addFlash(
                'warning',
                'Vous pourrez laisser un avis une fois la commande terminée.'
            );

            return $this->redirectToRoute(
                'app_mes_commandes',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $avisExistant = $entityManager->getRepository(Avis::class)
            ->findOneBy(['commande' => $commande]);

        if ($avisExistant !== null) {
            $this->addFlash('warning', 'Vous avez déjà laissé un avis pour cette commande.');

            return $this->redirectToRoute(
                'app_mes_commandes',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        $avis = (new Avis())->setCommande($commande);
        $form = $this->createForm(AvisType::class, $avis, [
            'csrf_token_id' => 'deposer_avis_' . $id,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($avis);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Merci pour votre avis. Il est en attente de modération.'
                );
            } catch (UniqueConstraintViolationException) {
                $this->addFlash(
                    'warning',
                    'Vous avez déjà laissé un avis pour cette commande.'
                );
            }

            return $this->redirectToRoute(
                'app_mes_commandes',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('commande/avis.html.twig', [
            'commande' => $commande,
            'avisForm' => $form->createView(),
        ]);
    }
}