<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\ThemeRepository;
use App\Repository\RegimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;


final class MenuController extends AbstractController
{
    #[Route('/menus', name: 'app_menu', methods: ['GET'])]
    public function index(
        Request $request,
        MenuRepository $menuRepository,
        ThemeRepository $themeRepository,
        RegimeRepository $regimeRepository
    ): Response {
        $prixMax = $request->query->get('prix_max', '');
        $themeSelectionne = $request->query->get('theme', '');
        $regimeSelectionne = $request->query->get('regime', '');
        $prixMaxCentimes = null;
        $themeId = null;
        $regimeId = null;

        if ($prixMax !== '') {
            if (
                !is_string($prixMax)
                || !preg_match('/^[0-9]{1,7}(?:\.[0-9]{1,2})?$/D', $prixMax)
            ) {
                throw new BadRequestHttpException(
                    'Le prix doit être positif ou nul, avec au maximum deux décimales.'
                );
            }

            $parties = explode('.', $prixMax);
            $euros = (int) $parties[0];
            $centimes = (int) str_pad($parties[1] ?? '', 2, '0');

            $prixMaxCentimes = $euros * 100 + $centimes;
        }

        if ($themeSelectionne !== '') {
            if (
                !is_string($themeSelectionne)
                || !preg_match('/^[1-9][0-9]{0,9}$/D', $themeSelectionne)
            ) {
                throw new BadRequestHttpException('Le thème est invalide.');
            }

            $themeId = (int) $themeSelectionne;

            if (
                $themeId > 2147483647
                || $themeRepository->find($themeId) === null
            ) {
                throw new BadRequestHttpException('Ce thème n’existe pas.');
            }
        }
                if ($regimeSelectionne !== '') {
            if (
                !is_string($regimeSelectionne)
                || !preg_match('/^[1-9][0-9]{0,9}$/D', $regimeSelectionne)
            ) {
                throw new BadRequestHttpException('Le régime est invalide.');
            }

            $regimeId = (int) $regimeSelectionne;

            if (
                $regimeId > 2147483647
                || $regimeRepository->find($regimeId) === null
            ) {
                throw new BadRequestHttpException('Ce régime n’existe pas.');
            }
        }

        $menus = $menuRepository->findByFiltres(
            $prixMaxCentimes,
            $themeId,
            $regimeId
        );

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
            'prix_max' => $prixMax,
            'themes' => $themeRepository->findBy([], ['libelle' => 'ASC']),
            'theme_selectionne' => $themeSelectionne,
            'regimes' => $regimeRepository->findBy([], ['libelle' => 'ASC']),
            'regime_selectionne' => $regimeSelectionne,
        ]);
    }

    #[Route(
        '/menus/{id}',
        name: 'app_menu_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(int $id, MenuRepository $menuRepository): Response
    {
        $menu = $menuRepository->find($id);

        if ($menu === null) {
            throw $this->createNotFoundException('Ce menu n’existe pas.');
        }

        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }
}