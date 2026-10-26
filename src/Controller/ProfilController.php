<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfilController extends AbstractController
{
    #[Route(
        '/mon-profil',
        name: 'app_profil',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof Utilisateur
            || !$utilisateur->isActif()
        ) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(
            ProfilType::class,
            $utilisateur
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a bien été mis à jour.'
            );

            return $this->redirectToRoute(
                'app_profil',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('profil/index.html.twig', [
            'profilForm' => $form->createView(),
            'email' => $utilisateur->getEmail(),
        ]);
    }
}