<?php

namespace App\Service;

final class CalculFraisLivraison
{
    public function calculer(
        bool $livraisonDansBordeaux,
        int $distanceMetres
    ): int {
        if ($distanceMetres < 0) {
            throw new \InvalidArgumentException(
                'La distance ne peut pas être négative.'
            );
        }

        if ($livraisonDansBordeaux) {
            return 0;
        }

        $partDistanceCentimes = intdiv(
            $distanceMetres * 59 + 500,
            1000
        );

        return 500 + $partDistanceCentimes;
    }
}