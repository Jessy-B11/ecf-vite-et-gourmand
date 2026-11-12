<?php

namespace App\Service;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class StatistiquesCommandes
{
    private ?Collection $collection = null;

    public function __construct(
        #[Autowire(env: 'MONGODB_URI')]
        private string $mongoUri,
        #[Autowire(env: 'MONGODB_DATABASE')]
        private string $mongoDatabase
    ) {
    }

    /** @return list<array{id: int, nom: string, commandes: int}> */
    public function compterParMenu(): array
    {
        $rows = $this->collection()->aggregate([
            ['$sort' => ['_id' => 1]],
            ['$group' => [
                '_id' => '$menu.id',
                'nom' => ['$last' => '$menu.nom'],
                'commandes' => ['$sum' => 1],
            ]],
            ['$sort' => ['nom' => 1, '_id' => 1]],
        ]);
        $menus = [];

        foreach ($rows as $row) {
            $menus[] = ['id' => (int) $row['_id'], 'nom' => $row['nom'], 'commandes' => (int) $row['commandes']];
        }

        return $menus;
    }

    /**
     * Les dates reçues sont des minuits Europe/Paris ; la fin est exclusive.
     * @return array{menus: list<array{id: int, nom: string, commandes: int, centimes: int}>, total_centimes: int, commandes: int}
     */
    public function chiffreAffaires(
        ?int $menu = null,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $finExclusive = null
    ): array {
        $match = ['statut' => 'terminee'];

        if ($menu !== null) {
            $match['menu.id'] = $menu;
        }
        if ($debut !== null) {
            $match['date_creation']['$gte'] = new UTCDateTime($debut);
        }
        if ($finExclusive !== null) {
            $match['date_creation']['$lt'] = new UTCDateTime($finExclusive);
        }

        $rows = $this->collection()->aggregate([
            ['$match' => $match],
            ['$sort' => ['_id' => 1]],
            ['$group' => [
                '_id' => '$menu.id',
                'nom' => ['$last' => '$menu.nom'],
                'commandes' => ['$sum' => 1],
                'centimes' => ['$sum' => ['$subtract' => ['$montant_menu_centimes', '$montant_remise_centimes']]],
            ]],
            ['$sort' => ['nom' => 1, '_id' => 1]],
        ]);
        $result = ['menus' => [], 'total_centimes' => 0, 'commandes' => 0];

        foreach ($rows as $row) {
            $centimes = (int) $row['centimes'];
            $commandes = (int) $row['commandes'];
            $result['menus'][] = [
                'id' => (int) $row['_id'], 'nom' => $row['nom'],
                'commandes' => $commandes, 'centimes' => $centimes,
            ];
            $result['total_centimes'] += $centimes;
            $result['commandes'] += $commandes;
        }

        return $result;
    }

    private function collection(): Collection
    {
        if ($this->collection === null) {
            $client = new Client($this->mongoUri, [
                'serverSelectionTimeoutMS' => 3000,
                'connectTimeoutMS' => 3000,
                'socketTimeoutMS' => 10000,
            ], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
            $this->collection = $client->selectCollection($this->mongoDatabase, 'commandes');
        }

        return $this->collection;
    }
}
