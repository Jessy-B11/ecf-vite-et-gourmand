<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;
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
        $prixMin = $request->query->get('prix_min', '');
        $themeSelectionne = $request->query->get('theme', '');
        $regimeSelectionne = $request->query->get('regime', '');
        $nombrePersonnesSaisi = $request->query->get('nombre_personnes', '');
        $prixMaxCentimes = null;
        $prixMinCentimes = null;
        $themeId = null;
        $regimeId = null;
        $nombrePersonnes = null;
        $erreurFiltres = null;

                if ($nombrePersonnesSaisi !== '') {
            if (
                !is_string($nombrePersonnesSaisi)
                || !preg_match('/^[1-9][0-9]{0,5}$/D', $nombrePersonnesSaisi)
            ) {
                $erreurFiltres = 'Le nombre de convives doit être un entier entre 1 et 999999.';
            } else {
                $nombrePersonnes = (int) $nombrePersonnesSaisi;
            }
        }

        if ($prixMax !== '') {
            if (
                !is_string($prixMax)
                || !preg_match('/^[0-9]{1,7}(?:\.[0-9]{1,2})?$/D', $prixMax)
            ) {
                throw new BadRequestHttpException(
                    'Le prix maximum doit être positif ou nul, avec au maximum deux décimales.'
                );
            }

            $parties = explode('.', $prixMax);
            $euros = (int) $parties[0];
            $centimes = (int) str_pad($parties[1] ?? '', 2, '0');

            $prixMaxCentimes = $euros * 100 + $centimes;
        }

        if ($prixMin !== '') {
            if (
                !is_string($prixMin)
                || !preg_match('/^[0-9]{1,7}(?:\.[0-9]{1,2})?$/D', $prixMin)
            ) {
                throw new BadRequestHttpException(
                    'Le prix minimum doit être positif ou nul, avec au maximum deux décimales.'
                );
            }

            $parties = explode('.', $prixMin);
            $euros = (int) $parties[0];
            $centimes = (int) str_pad($parties[1] ?? '', 2, '0');

            $prixMinCentimes = $euros * 100 + $centimes;
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

        if (
            $prixMinCentimes !== null
            && $prixMaxCentimes !== null
            && $prixMinCentimes > $prixMaxCentimes
        ) {
            $erreurFiltres = 'Le prix minimum ne peut pas dépasser le prix maximum.';
        }

        $menus = [];

        if ($erreurFiltres === null) {
            $menus = $menuRepository->findByFiltres(
                $prixMaxCentimes,
                $themeId,
                $regimeId,
                $prixMinCentimes,
                $nombrePersonnes
            );
        }

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
            'prix_max' => $prixMax,
            'prix_min' => $prixMin,
            'themes' => $themeRepository->findBy([], ['libelle' => 'ASC']),
            'theme_selectionne' => $themeSelectionne,
            'regimes' => $regimeRepository->findBy([], ['libelle' => 'ASC']),
            'regime_selectionne' => $regimeSelectionne,
            'erreur_filtres' => $erreurFiltres,
            'nombre_personnes' => $nombrePersonnesSaisi,
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