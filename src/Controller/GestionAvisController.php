<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('ROLE_EMPLOYE')]
final class GestionAvisController extends AbstractController
{
    #[Route('/gestion/avis', name: 'app_gestion_avis', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $employe = $this->getUser();

        if (!$employe instanceof Utilisateur || !$employe->isActif()) {
            throw $this->createAccessDeniedException();
        }

        $avisEnAttente = $entityManager->getRepository(Avis::class)->findBy(
            ['statutModeration' => 'en_attente'],
            ['dateCreation' => 'ASC', 'id' => 'ASC']
        );

        return $this->render('gestion/avis/index.html.twig', [
            'avisEnAttente' => $avisEnAttente,
        ]);
    }
        #[Route(
        '/gestion/avis/{id}/{decision}',
        name: 'app_gestion_avis_moderer',
        requirements: ['id' => '\d+', 'decision' => 'valide|refuse'],
        methods: ['POST']
    )]
    public function moderer(
        int $id,
        string $decision,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $employe = $this->getUser();

        if (!$employe instanceof Utilisateur || !$employe->isActif()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'moderer_avis_' . $id . '_' . $decision,
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire est invalide. Rechargez la page.'
            );
        }

        $nombreModifies = $entityManager->createQuery(
            'UPDATE App\Entity\Avis a
             SET a.statutModeration = :decision
             WHERE a.id = :id AND a.statutModeration = :enAttente'
        )
            ->setParameter('decision', $decision)
            ->setParameter('id', $id)
            ->setParameter('enAttente', 'en_attente')
            ->execute();

        if ($nombreModifies === 0) {
            $this->addFlash('warning', 'Cet avis a déjà été traité ou est introuvable.');
        } else {
            $this->addFlash(
                'success',
                $decision === 'valide' ? 'L’avis a été approuvé.' : 'L’avis a été refusé.'
            );
        }

        return $this->redirectToRoute(
            'app_gestion_avis',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}