<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Driver\WriteConcern;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SynchronisationStatistiques
{
    public function __construct(
        private Connection $connection,
        #[Autowire(env: 'MONGODB_URI')]
        private string $mongoUri,
        #[Autowire(env: 'MONGODB_DATABASE')]
        private string $mongoDatabase
    ) {
    }

    /** @return array{lues: int, creees: int, modifiees: int, inchangees: int} */
    public function synchroniser(): array
    {
        // Créer le client ici pour que la commande puisse aussi signaler une URI invalide.
        $client = new Client($this->mongoUri, [
            'serverSelectionTimeoutMS' => 3000,
            'connectTimeoutMS' => 3000,
            'socketTimeoutMS' => 10000,
        ]);
        $database = $client->selectDatabase($this->mongoDatabase);
        $database->command(['ping' => 1])->toArray();
        $collection = $database->selectCollection('commandes', [
            'writeConcern' => new WriteConcern(WriteConcern::MAJORITY, 5000),
        ]);

        // Liste explicite : aucune identité, coordonnée, clé client ou jeton ne sort de PostgreSQL.
        $rows = $this->connection->iterateAssociative(
            'SELECT c.id, c.menu_id, m.nom AS menu_nom, c.nombre_personnes,
                    c.date_creation, c.date_prestation, c.statut,
                    c.montant_menu_centimes, c.montant_remise_centimes,
                    c.frais_livraison_centimes, c.montant_total_centimes
             FROM commande c
             INNER JOIN menu m ON m.id = c.menu_id
             ORDER BY c.id'
        );
        $counts = ['lues' => 0, 'creees' => 0, 'modifiees' => 0, 'inchangees' => 0];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $document = [
                '_id' => $id,
                'menu' => ['id' => (int) $row['menu_id'], 'nom' => $row['menu_nom']],
                'nombre_personnes' => (int) $row['nombre_personnes'],
                'date_creation' => new UTCDateTime(new \DateTimeImmutable($row['date_creation'])),
                // Une date de prestation est un jour civil, sans heure ni conversion de fuseau.
                'date_prestation' => $row['date_prestation'],
                'statut' => $row['statut'],
                'montant_menu_centimes' => (int) $row['montant_menu_centimes'],
                'montant_remise_centimes' => (int) $row['montant_remise_centimes'],
                'frais_livraison_centimes' => (int) $row['frais_livraison_centimes'],
                'montant_total_centimes' => (int) $row['montant_total_centimes'],
            ];

            // Le remplacement supprime aussi d’éventuels anciens champs hors de cette liste.
            $result = $collection->replaceOne(['_id' => $id], $document, ['upsert' => true]);
            ++$counts['lues'];

            if ($result->getUpsertedCount() === 1) {
                ++$counts['creees'];
            } elseif ($result->getModifiedCount() === 1) {
                ++$counts['modifiees'];
            } else {
                ++$counts['inchangees'];
            }
        }

        return $counts;
    }
}
