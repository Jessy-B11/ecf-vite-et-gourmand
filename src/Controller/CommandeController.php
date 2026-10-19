<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Form\CommandeType;
use App\Repository\MenuRepository;
use App\Service\CalculPrixCommande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CommandeController extends AbstractController
{
    #[Route(
        '/menus/{id}/commander',
        name: 'app_commande',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        int $id,
        Request $request,
        MenuRepository $menuRepository,
        CalculPrixCommande $calculPrixCommande
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $menu = $menuRepository->find($id);

        if ($menu === null) {
            throw $this->createNotFoundException(
                'Ce menu n’existe pas.'
            );
        }

        if ($menu->getStockCommandes() <= 0) {
            $this->addFlash(
                'warning',
                'Ce menu est actuellement indisponible.'
            );

            return $this->redirectToRoute('app_menu_show', [
                'id' => $menu->getId(),
            ]);
        }

        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setNombrePersonnes($menu->getMinimumPersonnes());
        $commande->setAdresseLivraison(
            $utilisateur->getAdresse() ?? ''
        );
        $commande->setCodePostalLivraison(
            $utilisateur->getCodePostal() ?? ''
        );
        $commande->setVilleLivraison(
            $utilisateur->getVille() ?? ''
        );
        $commande->setTelephoneContact(
            $utilisateur->getTelephone() ?? ''
        );

        $dateMinimum = new \DateTimeImmutable(
            'today',
            new \DateTimeZone('Europe/Paris')
        );

        $dateMinimum = $dateMinimum->modify(
            sprintf('+%d days', $menu->getDelaiMinimumJours())
        );

        $form = $this->createForm(CommandeType::class, $commande, [
            'minimum_personnes' => $menu->getMinimumPersonnes(),
            'date_minimum' => $dateMinimum,
        ]);

        $form->handleRequest($request);

        $recapitulatif = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $nombrePersonnes = $commande->getNombrePersonnes();

            $montantMenu = $calculPrixCommande->calculerMontantMenu(
                $menu,
                $nombrePersonnes
            );

            $remise = $calculPrixCommande->calculerRemise(
                $menu,
                $nombrePersonnes
            );

            $recapitulatif = [
                'nombre_personnes' => $nombrePersonnes,
                'montant_menu' => $montantMenu,
                'remise' => $remise,
                'montant_apres_remise' => $montantMenu - $remise,
            ];
        }

        return $this->render('commande/index.html.twig', [
            'commandeForm' => $form->createView(),
            'menu' => $menu,
            'client' => $utilisateur,
            'recapitulatif' => $recapitulatif,
        ]);
    }
}