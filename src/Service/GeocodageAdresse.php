<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeocodageAdresse
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function rechercher(string $adresse): array
    {
        $adresse = trim($adresse);

        if ($adresse === '') {
            throw new \InvalidArgumentException(
                'L’adresse à rechercher ne peut pas être vide.'
            );
        }

        try {
            $reponse = $this->httpClient->request(
                'GET',
                'https://data.geopf.fr/geocodage/search',
                [
                    'query' => [
                        'q' => $adresse,
                        'limit' => 5,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 5,
                    'max_duration' => 10,
                ]
            );

            $donnees = $reponse->toArray();
        } catch (ExceptionInterface $exception) {
            throw new \RuntimeException(
                'Le service de recherche d’adresse est momentanément indisponible.',
                0,
                $exception
            );
        }

        $propositions = $donnees['features'] ?? null;

        if (!is_array($propositions)) {
            throw new \RuntimeException(
                'Le service de recherche d’adresse a renvoyé une réponse inattendue.'
            );
        }

        return $propositions;
    }
}