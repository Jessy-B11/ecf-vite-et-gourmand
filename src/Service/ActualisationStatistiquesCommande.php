<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

final class ActualisationStatistiquesCommande
{
    public function __construct(
        private SynchronisationStatistiques $synchronisation,
        private LoggerInterface $logger
    ) {
    }

    public function apresValidation(int $commandeId): void
    {
        try {
            $this->synchronisation->synchroniserCommande($commandeId);
        } catch (\Throwable $exception) {
            // Aucun message d’exception ni objet exception : ils pourraient contenir l’URI Atlas.
            $this->logger->error('Statistiques de commande non actualisées ; rattrapage par app:stats:sync nécessaire.', [
                'commande_id' => $commandeId,
                'type_erreur' => $exception::class,
            ]);
        }
    }
}
