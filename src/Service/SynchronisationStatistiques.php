<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use MongoDB\UpdateResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SynchronisationStatistiques
{
    private ?Collection $collection = null;

    private const SELECT_COMMANDE =
        'SELECT c.id, c.menu_id, m.nom AS menu_nom, c.nombre_personnes,
                c.date_creation, c.date_prestation, c.statut, c.version_statistiques,
                c.montant_menu_centimes, c.montant_remise_centimes,
                c.frais_livraison_centimes, c.montant_total_centimes
         FROM commande c INNER JOIN menu m ON m.id = c.menu_id';

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
        $this->collection(); // Vérifier MongoDB même si la source SQL est vide.
        $rows = $this->connection->iterateAssociative(self::SELECT_COMMANDE . ' ORDER BY c.id');
        $counts = ['lues' => 0, 'creees' => 0, 'modifiees' => 0, 'inchangees' => 0];

        foreach ($rows as $row) {
            $result = $this->projeter($row);
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

    public function synchroniserCommande(int $commandeId): void
    {
        if ($this->connection->isTransactionActive()) {
            throw new \LogicException('La transaction PostgreSQL doit être validée avant la synchronisation ciblée.');
        }

        // Relire l’état validé, jamais l’entité ORM ou les données d’une ancienne requête.
        $row = $this->connection->fetchAssociative(self::SELECT_COMMANDE . ' WHERE c.id = ?', [$commandeId]);
        if ($row !== false) {
            $this->projeter($row);
        }
    }

    private function projeter(array $row): UpdateResult
    {
        // Liste explicite : aucune identité, coordonnée, clé client ou jeton ne sort de PostgreSQL.
        $document = [
            '_id' => (int) $row['id'],
            'menu' => ['id' => (int) $row['menu_id'], 'nom' => $row['menu_nom']],
            'nombre_personnes' => (int) $row['nombre_personnes'],
            'date_creation' => new UTCDateTime(new \DateTimeImmutable($row['date_creation'])),
            'date_prestation' => $row['date_prestation'],
            'statut' => $row['statut'],
            'montant_menu_centimes' => (int) $row['montant_menu_centimes'],
            'montant_remise_centimes' => (int) $row['montant_remise_centimes'],
            'frais_livraison_centimes' => (int) $row['frais_livraison_centimes'],
            'montant_total_centimes' => (int) $row['montant_total_centimes'],
            'version_source' => (int) $row['version_statistiques'],
        ];

        // Comparaison ET remplacement atomiques : une écriture retardée ne peut pas régresser l’état.
        // $literal protège aussi les noms de menus commençant par un dollar.
        return $this->collection()->updateOne(['_id' => $document['_id']], [
            ['$replaceWith' => ['$cond' => [
                ['$lte' => [['$ifNull' => ['$version_source', 0]], $document['version_source']]],
                ['$literal' => $document],
                '$$ROOT',
            ]]],
        ], ['upsert' => true]);
    }

    private function collection(): Collection
    {
        if ($this->collection === null) {
            $client = new Client($this->mongoUri, [
                'serverSelectionTimeoutMS' => 3000,
                'connectTimeoutMS' => 3000,
                'socketTimeoutMS' => 10000,
            ]);
            $database = $client->selectDatabase($this->mongoDatabase);
            $database->command(['ping' => 1])->toArray();
            $this->collection = $database->selectCollection('commandes', [
                'writeConcern' => new WriteConcern(WriteConcern::MAJORITY, 5000),
            ]);
        }

        return $this->collection;
    }
}
