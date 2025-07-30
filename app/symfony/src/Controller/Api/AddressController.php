<?php

namespace App\Controller\Api;

use App\Entity\Address;
use App\Repository\AddressRepository;
use App\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/address', name: 'api_address_')]
class AddressController extends AbstractController
{
    public function __construct(
        private AddressService $addressService,
        private AddressRepository $addressRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    /**
     * Recherche d'adresses avec autocomplétion via API data.gouv.fr
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchAddresses(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min((int) $request->query->get('limit', 10), 20);

        if (strlen($query) < 3) {
            return $this->json([
                'suggestions' => [],
                'message' => 'Saisissez au moins 3 caractères pour rechercher une adresse'
            ]);
        }

        try {
            $suggestions = $this->addressService->searchAddresses($query, $limit);
            
            // Formatage des suggestions pour le frontend
            $formattedSuggestions = array_map(function ($suggestion) {
                return [
                    'id' => null, // ID temporaire pour le frontend
                    'label' => $suggestion['label'], // Adresse complète affichée
                    'streetAddress' => trim($suggestion['housenumber'] . ' ' . $suggestion['street']),
                    'city' => $suggestion['city'],
                    'postalCode' => $suggestion['postcode'],
                    'country' => 'France',
                    'latitude' => $suggestion['latitude'],
                    'longitude' => $suggestion['longitude'],
                    'score' => $suggestion['score'],
                    'context' => $suggestion['context'] ?? ''
                ];
            }, $suggestions);
            
            return $this->json([
                'suggestions' => $formattedSuggestions,
                'count' => count($formattedSuggestions),
                'query' => $query
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la recherche d\'adresses',
                'suggestions' => [],
                'message' => 'Service temporairement indisponible'
            ], 500);
        }
    }

    /**
     * Validation complète d'une adresse
     */
    #[Route('/validate', name: 'validate', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function validateAddress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['streetAddress'], $data['city'], $data['postalCode'])) {
            return $this->json([
                'valid' => false,
                'errors' => [
                    'general' => 'Données incomplètes : adresse, ville et code postal requis'
                ]
            ], 400);
        }

        try {
            // Validation via AddressService
            $validationErrors = $this->addressService->validateFrenchAddress(
                $data['streetAddress'],
                $data['city'],
                $data['postalCode']
            );

            // Validation avec les contraintes Symfony
            $tempAddress = new Address();
            $tempAddress->setStreetAddress($data['streetAddress']);
            $tempAddress->setCity($data['city']);
            $tempAddress->setPostalCode($data['postalCode']);
            $tempAddress->setCountry($data['country'] ?? 'France');

            $violations = $this->validator->validate($tempAddress);
            
            // Conversion des violations en erreurs
            foreach ($violations as $violation) {
                $field = $violation->getPropertyPath();
                $validationErrors[$field] = $violation->getMessage();
            }

            $isValid = empty($validationErrors);
            
            $response = [
                'valid' => $isValid,
                'errors' => $validationErrors
            ];

            // Si valide, enrichir avec coordonnées et données normalisées
            if ($isValid) {
                $normalized = $this->addressService->normalizeAddress(
                    $data['streetAddress'],
                    $data['city'],
                    $data['postalCode']
                );

                // Rechercher les coordonnées
                $searchQuery = $tempAddress->getFullAddress();
                $geoResults = $this->addressService->searchAddresses($searchQuery, 1);
                
                if (!empty($geoResults) && $geoResults[0]['score'] > 0.5) {
                    $normalized['latitude'] = $geoResults[0]['latitude'];
                    $normalized['longitude'] = $geoResults[0]['longitude'];
                    $normalized['score'] = $geoResults[0]['score'];
                }

                $response['normalized'] = $normalized;
            }

            return $this->json($response);
            
        } catch (\Exception $e) {
            return $this->json([
                'valid' => false,
                'errors' => [
                    'general' => 'Erreur lors de la validation de l\'adresse'
                ]
            ], 500);
        }
    }

    /**
     * Création ou récupération d'une adresse
     */
    #[Route('/create-or-get', name: 'create_or_get', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createOrGetAddress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['streetAddress'], $data['city'], $data['postalCode'])) {
            return $this->json([
                'success' => false,
                'error' => 'Données incomplètes'
            ], 400);
        }

        try {
            // Validation préalable
            $validationErrors = $this->addressService->validateFrenchAddress(
                $data['streetAddress'],
                $data['city'],
                $data['postalCode']
            );

            if (!empty($validationErrors)) {
                return $this->json([
                    'success' => false,
                    'errors' => $validationErrors
                ], 400);
            }

            // Rechercher ou créer l'adresse
            $address = $this->addressRepository->findOrCreateSimilar(
                $data['streetAddress'],
                $data['city'],
                $data['postalCode'],
                $data['country'] ?? 'France'
            );

            // Enrichir avec les coordonnées si nécessaire
            if (!$address->hasCoordinates()) {
                $this->addressService->enrichAddressWithCoordinates($address);
            }

            // Sauvegarder si nouvelle adresse
            if ($address->getId() === null) {
                $this->entityManager->flush();
            }

            return $this->json([
                'success' => true,
                'address' => [
                    'id' => $address->getId(),
                    'streetAddress' => $address->getStreetAddress(),
                    'city' => $address->getCity(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'fullAddress' => $address->getFullAddress(),
                    'latitude' => $address->getLatitude(),
                    'longitude' => $address->getLongitude(),
                    'hasCoordinates' => $address->hasCoordinates()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la création de l\'adresse'
            ], 500);
        }
    }

    /**
     * Géocodage inverse - obtenir adresse depuis coordonnées
     */
    #[Route('/reverse-geocode', name: 'reverse_geocode', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function reverseGeocode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['latitude'], $data['longitude'])) {
            return $this->json([
                'success' => false,
                'error' => 'Coordonnées latitude et longitude requises'
            ], 400);
        }

        try {
            $result = $this->addressService->reverseGeocode(
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            if ($result === null) {
                return $this->json([
                    'success' => false,
                    'error' => 'Aucune adresse trouvée pour ces coordonnées'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'address' => [
                    'streetAddress' => trim($result['housenumber'] . ' ' . $result['street']),
                    'city' => $result['city'],
                    'postalCode' => $result['postcode'],
                    'country' => 'France',
                    'label' => $result['label']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du géocodage inverse'
            ], 500);
        }
    }

    /**
     * Recherche d'adresses par proximité géographique
     */
    #[Route('/nearby', name: 'nearby', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function findNearbyAddresses(Request $request): JsonResponse
    {
        $latitude = $request->query->get('lat');
        $longitude = $request->query->get('lng');
        $radius = min((float) $request->query->get('radius', 10), 50); // Max 50km

        if (!$latitude || !$longitude) {
            return $this->json([
                'success' => false,
                'error' => 'Coordonnées latitude et longitude requises'
            ], 400);
        }

        try {
            $nearbyAddresses = $this->addressRepository->findByProximity(
                (float) $latitude,
                (float) $longitude,
                $radius
            );

            $formattedAddresses = array_map(function (Address $address) {
                return [
                    'id' => $address->getId(),
                    'label' => $address->getFullAddress(),
                    'streetAddress' => $address->getStreetAddress(),
                    'city' => $address->getCity(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'latitude' => $address->getLatitude(),
                    'longitude' => $address->getLongitude()
                ];
            }, $nearbyAddresses);

            return $this->json([
                'success' => true,
                'addresses' => $formattedAddresses,
                'count' => count($formattedAddresses),
                'center' => [
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude
                ],
                'radius' => $radius
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la recherche par proximité'
            ], 500);
        }
    }
}