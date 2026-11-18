<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ItineraireLivraison
{
    public function __construct(
        private HttpClientInterface $httpClient,

        #[Autowire(env: 'ORS_API_KEY')]
        private string $cleApi,

        #[Autowire(env: 'float:LIVRAISON_DEPART_LONGITUDE')]
        private float $longitudeDepart,

        #[Autowire(env: 'float:LIVRAISON_DEPART_LATITUDE')]
        private float $latitudeDepart
    ) {
    }

    public function calculerDistance(
        float $longitudeArrivee,
        float $latitudeArrivee
    ): int {
        $this->verifierCoordonnees(
            $this->longitudeDepart,
            $this->latitudeDepart
        );

        $this->verifierCoordonnees(
            $longitudeArrivee,
            $latitudeArrivee
        );

        $cleApi = trim($this->cleApi);

        if ($cleApi === '') {
            throw new \RuntimeException(
                'La clé OpenRouteService est manquante.'
            );
        }

        try {
            $reponse = $this->httpClient->request(
                'POST',
                'https://api.heigit.org/openrouteservice/v2/directions/driving-car/json',
                [
                    'headers' => [
                        'Authorization' => $cleApi,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'coordinates' => [
                            [
                                $this->longitudeDepart,
                                $this->latitudeDepart,
                            ],
                            [
                                $longitudeArrivee,
                                $latitudeArrivee,
                            ],
                        ],
                        'preference' => 'fastest',
                        'units' => 'm',
                        'geometry' => false,
                        'instructions' => false,
                    ],
                    'timeout' => 5,
                    'max_duration' => 15,
                ]
            );

            $donnees = $reponse->toArray();
        } catch (ExceptionInterface $exception) {
            throw new \RuntimeException(
                'Le calcul de distance est momentanément indisponible.',
                0,
                $exception
            );
        }

        $distance = $donnees['routes'][0]['summary']['distance'] ?? null;

        if (
            (!is_int($distance) && !is_float($distance))
            || !is_finite((float) $distance)
            || $distance < 0
            || $distance >= PHP_INT_MAX
        ) {
            throw new \RuntimeException(
                'Le service n’a pas renvoyé de distance exploitable.'
            );
        }

        return (int) round($distance);
    }

    private function verifierCoordonnees(
        float $longitude,
        float $latitude
    ): void {
        if (
            !is_finite($longitude)
            || !is_finite($latitude)
            || $longitude < -180
            || $longitude > 180
            || $latitude < -90
            || $latitude > 90
        ) {
            throw new \InvalidArgumentException(
                'Les coordonnées géographiques sont invalides.'
            );
        }
    }
}