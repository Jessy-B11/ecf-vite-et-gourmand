<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\EmployeType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminEmployeController extends AbstractController
{
    #[Route(
        '/admin/employes/creer',
        name: 'app_admin_employe_creer',
        methods: ['GET', 'POST']
    )]
    public function creer(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $gerant = $this->getUser();

        if (!$gerant instanceof Utilisateur || !$gerant->isActif()) {
            throw $this->createAccessDeniedException();
        }

        $employe = new Utilisateur();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $employe->setRoles(['ROLE_EMPLOYE']);
            $employe->setActif(true);
            $employe->setPassword(
                $userPasswordHasher->hashPassword($employe, $plainPassword)
            );

            try {
                $entityManager->persist($employe);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                // Une autre requête peut avoir créé cette adresse après la validation.
                $form->get('email')->addError(new FormError(
                    'Un compte existe déjà avec cette adresse email.'
                ));
            }

            if ($form->isValid()) {
                $this->addFlash('success', 'Le compte employé a bien été créé.');

                return $this->redirectToRoute(
                    'app_admin_employe_creer',
                    [],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->render('admin/employe/creer.html.twig', [
            'employeForm' => $form->createView(),
        ]);
    }
}
