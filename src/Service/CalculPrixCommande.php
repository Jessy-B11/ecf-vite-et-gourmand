<?php

namespace App\Service;

use App\Entity\Menu;

final class CalculPrixCommande
{
    public function calculerMontantMenu(
        Menu $menu,
        int $nombrePersonnes
    ): int {
        $minimumPersonnes = $menu->getMinimumPersonnes();
        $prixMinimumCentimes = $menu->getPrixMinimumCentimes();

        if (
            $minimumPersonnes === null
            || $minimumPersonnes <= 0
            || $prixMinimumCentimes === null
            || $prixMinimumCentimes <= 0
        ) {
            throw new \InvalidArgumentException(
                'Le minimum et le prix du menu doivent être renseignés et positifs.'
            );
        }

        if ($nombrePersonnes < $minimumPersonnes) {
            throw new \InvalidArgumentException(
                'Le nombre de personnes est inférieur au minimum du menu.'
            );
        }

        $numerateur = $prixMinimumCentimes * $nombrePersonnes;

        return intdiv(
            $numerateur + intdiv($minimumPersonnes, 2),
            $minimumPersonnes
        );
    }
            public function calculerRemise(
        Menu $menu,
        int $nombrePersonnes
    ): int {
        $montantMenuCentimes = $this->calculerMontantMenu(
            $menu,
            $nombrePersonnes
        );

        if ($nombrePersonnes < $menu->getMinimumPersonnes() + 5) {
            return 0;
        }

        return intdiv($montantMenuCentimes + 5, 10);
    }
            public function calculerTotal(
        Menu $menu,
        int $nombrePersonnes,
        int $fraisLivraisonCentimes
    ): int {
        if ($fraisLivraisonCentimes < 0) {
            throw new \InvalidArgumentException(
                'Les frais de livraison ne peuvent pas être négatifs.'
            );
        }

        $montantMenu = $this->calculerMontantMenu(
            $menu,
            $nombrePersonnes
        );

        $remise = $this->calculerRemise(
            $menu,
            $nombrePersonnes
        );

        return $montantMenu - $remise + $fraisLivraisonCentimes;
    }
}