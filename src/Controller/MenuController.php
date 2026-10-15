<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MenuController extends AbstractController
{
    #[Route('/menus', name: 'app_menu', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): Response
    {
        $menus = $menuRepository->findAll();

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
        ]);
    }
    #[Route('/menus/{id}', name: 'app_menu_show', requirements: ['id' => '\d+'], methods: ['GET'])]
public function show(int $id, MenuRepository $menuRepository): Response
{
    $menu = $menuRepository->find($id);

    if ($menu === null) {
        throw $this->createNotFoundException('Ce menu existe pas.');
    }

    return $this->render('menu/show.html.twig', [
        'menu' => $menu,
    ]);
}
}