<?php

namespace App\Twig;

use App\Repository\HoraireJourRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class HorairesExtension extends AbstractExtension
{
    private const JOURS = [1 => 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    /** @var \WeakMap<Request, array> */
    private \WeakMap $parRequete;

    public function __construct(
        private HoraireJourRepository $repository,
        private RequestStack $requestStack
    ) {
        $this->parRequete = new \WeakMap();
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('horaires_footer', [$this, 'getHoraires'])];
    }

    /** @return list<array{jour: string, ferme: bool, creneaux: list<string>}> */
    public function getHoraires(): array
    {
        $request = $this->requestStack->getMainRequest();
        if ($request !== null && isset($this->parRequete[$request])) {
            return $this->parRequete[$request];
        }

        $semaine = [];
        foreach (self::JOURS as $numero => $libelle) {
            $semaine[$numero] = ['jour' => $libelle, 'ferme' => false, 'creneaux' => []];
        }

        foreach ($this->repository->findSemaineAvecCreneaux() as $jour) {
            $numero = $jour->getJourSemaine();
            $semaine[$numero]['ferme'] = $jour->isFerme();
            if (!$jour->isFerme()) {
                foreach ($jour->getCreneaux() as $creneau) {
                    $semaine[$numero]['creneaux'][] = $creneau->getHeureOuverture()->format('H\hi')
                        . '–' . $creneau->getHeureFermeture()->format('H\hi');
                }
            }
        }

        $horaires = array_values($semaine);
        if ($request !== null) {
            // Mémoïsation limitée à la requête : aucune ancienne semaine entre deux pages.
            $this->parRequete[$request] = $horaires;
        }

        return $horaires;
    }
}
