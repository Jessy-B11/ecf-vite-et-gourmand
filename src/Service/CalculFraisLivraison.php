<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CalculFraisLivraison
{
    public function __construct(
        #[Autowire(env: 'int:LIVRAISON_DISTANCE_MAX_METRES')]
        private int $distanceMaxMetres = 100000
    ) {
    }

    public function verifierZone(
        bool $livraisonDansBordeaux,
        int $distanceMetres
    ): void {
        if ($this->distanceMaxMetres <= 0) {
            throw new \LogicException(
                'La distance maximale de livraison doit être positive.'
            );
        }

        if ($distanceMetres < 0) {
            throw new \InvalidArgumentException(
                'La distance de livraison ne peut pas être négative.'
            );
        }

        if (
            !$livraisonDansBordeaux
            && $distanceMetres > $this->distanceMaxMetres
        ) {
            throw new \DomainException(
                sprintf(
                    'Cette adresse dépasse notre zone de livraison de %g km de trajet routier aller depuis le traiteur.',
                    $this->distanceMaxMetres / 1000
                )
            );
        }
    }

    public function calculer(
        bool $livraisonDansBordeaux,
        int $distanceMetres
    ): int {
        $this->verifierZone(
            $livraisonDansBordeaux,
            $distanceMetres
        );

        if ($livraisonDansBordeaux) {
            return 0;
        }

        return 500 + intdiv(
            $distanceMetres * 59 + 500,
            1000
        );
    }
}