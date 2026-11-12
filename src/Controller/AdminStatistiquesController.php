<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\StatistiquesCommandes;
use MongoDB\Driver\Exception\Exception as MongoException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminStatistiquesController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'app_admin_statistiques', methods: ['GET'])]
    public function index(Request $request, StatistiquesCommandes $statistiques): Response
    {
        $gerant = $this->getUser();

        if (!$gerant instanceof Utilisateur || !$gerant->isActif()) {
            throw $this->createAccessDeniedException();
        }

        $parameters = $request->query->all();
        $filtres = ['menu' => '', 'debut' => '', 'fin' => ''];
        $erreurs = [];

        foreach ($filtres as $key => $value) {
            if (isset($parameters[$key]) && !is_string($parameters[$key])) {
                $erreurs[] = 'Les filtres doivent contenir une seule valeur par champ.';
            } else {
                $filtres[$key] = trim($parameters[$key] ?? '', " \t\r\n");
            }
        }

        $menu = null;
        if ($filtres['menu'] !== '') {
            $menu = filter_var($filtres['menu'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 2147483647],
            ]);
            if ($menu === false) {
                $erreurs[] = 'Choisissez un menu valide.';
                $menu = null;
            }
        }

        $debut = $this->lireDate($filtres['debut'], 'début', $erreurs);
        $fin = $this->lireDate($filtres['fin'], 'fin', $erreurs);
        if ($debut !== null && $fin !== null && $debut > $fin) {
            $erreurs[] = 'La date de début doit précéder ou être égale à la date de fin.';
        }

        $menus = [];
        $ca = null;
        $indisponible = !extension_loaded('mongodb');

        if (!$indisponible) {
            try {
                $menus = $statistiques->compterParMenu();
                if ($menu !== null && !in_array($menu, array_column($menus, 'id'), true)) {
                    $erreurs[] = 'Le menu sélectionné n’est pas disponible dans les statistiques.';
                }
                if ($erreurs === []) {
                    // Ajouter un jour civil, et non 86 400 secondes, pour les changements d’heure.
                    $ca = $statistiques->chiffreAffaires($menu, $debut, $fin?->modify('+1 day'));
                }
            } catch (MongoException) {
                // Les exceptions MongoDB peuvent contenir des identifiants : ne pas les afficher.
                $indisponible = true;
            }
        }

        $status = $indisponible ? Response::HTTP_SERVICE_UNAVAILABLE
            : ($erreurs !== [] ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);

        return $this->render('admin/statistiques/index.html.twig', [
            'menus' => $menus,
            'totalCommandes' => array_sum(array_column($menus, 'commandes')),
            'maximumCommandes' => max(array_column($menus, 'commandes') ?: [1]),
            'ca' => $ca,
            'filtres' => $filtres,
            'erreurs' => array_unique($erreurs),
            'indisponible' => $indisponible,
        ], new Response(status: $status));
    }

    /** @param list<string> $erreurs */
    private function lireDate(string $value, string $label, array &$erreurs): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('Europe/Paris')) : false;
        if ($date === false || $date->format('Y-m-d') !== $value || (int) $date->format('Y') < 1) {
            $erreurs[] = sprintf('La date de %s doit être une date valide au format AAAA-MM-JJ.', $label);

            return null;
        }

        return $date;
    }
}
