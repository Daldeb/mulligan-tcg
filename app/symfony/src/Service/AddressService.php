<?php

namespace App\Service;

use App\Entity\Address;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AddressService
{
    private const DATA_GOUV_BASE_URL = 'https://api-adresse.data.gouv.fr';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Recherche d'adresses via l'API data.gouv.fr
     */
    public function searchAddresses(string $query, int $limit = 10): array
    {
        if (strlen(trim($query)) < 3) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::DATA_GOUV_BASE_URL . '/search/', [
                'query' => [
                    'q' => $query,
                    'limit' => $limit,
                    'type' => 'housenumber'
                ],
                'timeout' => 5
            ]);

            $data = $response->toArray();
            
            if (!isset($data['features'])) {
                return [];
            }

            return array_map(function ($feature) {
                $properties = $feature['properties'];
                $coordinates = $feature['geometry']['coordinates'];

                return [
                    'label' => $properties['label'],
                    'name' => $properties['name'] ?? '',
                    'city' => $properties['city'],
                    'postcode' => $properties['postcode'],
                    'context' => $properties['context'] ?? '',
                    'street' => $properties['street'] ?? $properties['name'] ?? '',
                    'housenumber' => $properties['housenumber'] ?? '',
                    'latitude' => $coordinates[1],
                    'longitude' => $coordinates[0],
                    'score' => $properties['score']
                ];
            }, $data['features']);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche d\'adresses', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Géocodage inverse - obtenir l'adresse depuis des coordonnées
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::DATA_GOUV_BASE_URL . '/reverse/', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'type' => 'housenumber'
                ],
                'timeout' => 5
            ]);

            $data = $response->toArray();
            
            if (!isset($data['features'][0])) {
                return null;
            }

            $feature = $data['features'][0];
            $properties = $feature['properties'];

            return [
                'label' => $properties['label'],
                'name' => $properties['name'] ?? '',
                'city' => $properties['city'],
                'postcode' => $properties['postcode'],
                'street' => $properties['street'] ?? '',
                'housenumber' => $properties['housenumber'] ?? ''
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du géocodage inverse', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Valide une adresse française
     */
    public function validateFrenchAddress(string $streetAddress, string $city, string $postalCode): array
    {
        $errors = [];

        // Validation du code postal français
        if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            $errors['postalCode'] = 'Le code postal doit contenir exactement 5 chiffres';
        }

        // Validation de la ville
        if (strlen($city) < 2) {
            $errors['city'] = 'Le nom de la ville doit contenir au moins 2 caractères';
        }

        // Validation de l'adresse
        if (strlen($streetAddress) < 5) {
            $errors['streetAddress'] = 'L\'adresse doit contenir au moins 5 caractères';
        }

        // Validation croisée via API si pas d'erreurs basiques
        if (empty($errors)) {
            $searchQuery = "$streetAddress, $postalCode $city";
            $suggestions = $this->searchAddresses($searchQuery, 1);
            
            if (empty($suggestions)) {
                $errors['address'] = 'Adresse introuvable dans la base adresse nationale';
            } elseif ($suggestions[0]['score'] < 0.4) {
                $errors['address'] = 'Adresse imprécise ou incomplète';
            }
        }

        return $errors;
    }

    /**
     * Enrichit une entité Address avec les coordonnées géographiques
     */
    public function enrichAddressWithCoordinates(Address $address): void
    {
        if ($address->hasCoordinates()) {
            return; // Déjà enrichie
        }

        $query = $address->getFullAddress();
        $suggestions = $this->searchAddresses($query, 1);

        if (!empty($suggestions) && $suggestions[0]['score'] > 0.5) {
            $address->setCoordinates(
                $suggestions[0]['latitude'],
                $suggestions[0]['longitude']
            );
        }
    }

    /**
     * Normalise une adresse (met en forme)
     */
    public function normalizeAddress(string $streetAddress, string $city, string $postalCode): array
    {
        return [
            'streetAddress' => ucwords(strtolower(trim($streetAddress))),
            'city' => strtoupper(trim($city)),
            'postalCode' => trim($postalCode)
        ];
    }
}