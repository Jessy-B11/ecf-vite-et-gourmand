<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $avisValides = $entityManager->getRepository(Avis::class)->findBy(
            ['statutModeration' => 'valide'],
            ['dateCreation' => 'DESC', 'id' => 'DESC'],
            6
        );

        $menusDuMoment = $entityManager->getRepository(Menu::class)->createQueryBuilder('menu')
            ->andWhere('menu.stockCommandes > 0')
            ->orderBy('menu.prixMinimumCentimes', 'ASC')
            ->addOrderBy('menu.id', 'ASC')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        return $this->render('accueil/index.html.twig', [
            'avisValides' => $avisValides,
            'menusDuMoment' => $menusDuMoment,
        ]);
    }
}
