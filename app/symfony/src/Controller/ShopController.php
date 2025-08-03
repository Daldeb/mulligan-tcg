<?php

namespace App\Controller;

use App\Entity\Shop;
use App\Repository\ShopRepository;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shops', name: 'api_shop_')]
class ShopController extends AbstractController
{
    public function __construct(
        private ShopRepository $shopRepository,
        private GameRepository $gameRepository
    ) {}

    /**
     * Liste de toutes les boutiques avec filtres optionnels
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query->get('type'),
            'department' => $request->query->get('department'),
            'game' => $request->query->get('game'),
            'service' => $request->query->get('service')
        ];

        // Filtrer les valeurs nulles
        $filters = array_filter($filters, fn($value) => $value !== null);

        $shops = $this->shopRepository->findForApi($filters);

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'formatShopForApi'], $shops)
        ]);
    }

    /**
     * Données optimisées pour affichage carte
     */
    #[Route('/map', name: 'map', methods: ['GET'])]
    public function map(): JsonResponse
    {
        $shops = $this->shopRepository->findForMap();

        return $this->json([
            'success' => true,
            'data' => $shops
        ]);
    }

    /**
     * Boutiques populaires pour HomeView
     */
    #[Route('/popular', name: 'popular', methods: ['GET'])]
    public function popular(): JsonResponse
    {
        $shops = $this->shopRepository->findMostPopular(6);

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'formatShopForApi'], $shops)
        ]);
    }

    /**
     * Boutiques à proximité (géolocalisation)
     */
    #[Route('/nearby', name: 'nearby', methods: ['GET'])]
    public function nearby(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $radius = $request->query->get('radius', 50);

        if (!$lat || !$lng) {
            return $this->json([
                'success' => false,
                'error' => 'Latitude et longitude requis'
            ], 400);
        }

        $nearbyShops = $this->shopRepository->findNearby(
            (float) $lat,
            (float) $lng,
            (float) $radius
        );

        $data = array_map(function($item) {
            return [
                'shop' => $this->formatShopForApi($item['shop']),
                'distance' => round($item['distance'], 1)
            ];
        }, $nearbyShops);

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Recherche flexible
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');

        if (!$query || strlen($query) < 2) {
            return $this->json([
                'success' => false,
                'error' => 'Requête de recherche trop courte (minimum 2 caractères)'
            ], 400);
        }

        $shops = $this->shopRepository->search($query);

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'formatShopForApi'], $shops)
        ]);
    }

    /**
     * Liste des départements avec compteurs
     */
    #[Route('/departments', name: 'departments', methods: ['GET'])]
    public function departments(): JsonResponse
    {
        // Requête native pour grouper par département
        $conn = $this->shopRepository->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                LEFT(a.postal_code, 2) as department,
                COUNT(s.id) as count
            FROM shop s
            INNER JOIN address a ON s.address_id = a.id
            WHERE s.is_active = 1
            GROUP BY department
            ORDER BY department ASC
        ";
        
        $result = $conn->executeQuery($sql);
        $departments = $result->fetchAllAssociative();

        return $this->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Détails d'une boutique par ID
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $shop = $this->shopRepository->find($id);

        if (!$shop || !$shop->isActive()) {
            return $this->json([
                'success' => false,
                'error' => 'Boutique non trouvée'
            ], 404);
        }

        // Incrémenter les vues
        $shop->incrementStat('views');
        $this->shopRepository->save($shop, true);

        return $this->json([
            'success' => true,
            'data' => $this->formatShopForApi($shop, true)
        ]);
    }

    /**
     * Détails d'une boutique par slug
     */
    #[Route('/slug/{slug}', name: 'show_by_slug', methods: ['GET'])]
    public function showBySlug(string $slug): JsonResponse
    {
        $shop = $this->shopRepository->findBySlug($slug);

        if (!$shop) {
            return $this->json([
                'success' => false,
                'error' => 'Boutique non trouvée'
            ], 404);
        }

        // Incrémenter les vues
        $shop->incrementStat('views');
        $this->shopRepository->save($shop, true);

        return $this->json([
            'success' => true,
            'data' => $this->formatShopForApi($shop, true)
        ]);
    }

    /**
     * Statistiques générales des boutiques
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $stats = $this->shopRepository->getStats();

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Formate une boutique pour l'API
     */
    private function formatShopForApi(Shop $shop, bool $detailed = false): array
    {
        $data = [
            'id' => $shop->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'description' => $shop->getDescription(),
            'type' => $shop->getType(),
            'status' => $shop->getStatus(),
            'phone' => $shop->getPhone(),
            'website' => $shop->getWebsite(),
            'email' => $shop->getEmail(),
            'logo' => $shop->getLogo(),
            'primaryColor' => $shop->getPrimaryColor(),
            'services' => $shop->getServices() ?? [],
            'specializedGames' => $shop->getSpecializedGames() ?? [],
            'isFeatured' => $shop->isFeatured(),
            'isVerified' => $shop->isVerified(),
            'isClaimed' => $shop->isClaimed(),
            'stats' => [
                'views' => $shop->getViewsCount(),
                'rating' => $shop->getAverageRating(),
                'reviewsCount' => $shop->getStats()['reviews_count'] ?? 0
            ],
            'address' => null,
            'coordinates' => null,
            'owner' => null,
            'games' => []
        ];

        // Adresse
        if ($shop->getAddress()) {
            $address = $shop->getAddress();
            $data['address'] = [
                'street' => $address->getStreetAddress(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalCode(),
                'country' => $address->getCountry(),
                'full' => $address->getFullAddress()
            ];

            $data['coordinates'] = [
                'lat' => $address->getLatitude(),
                'lng' => $address->getLongitude()
            ];
        }

        // Propriétaire (si revendiquée)
        if ($shop->getOwner()) {
            $owner = $shop->getOwner();
            $data['owner'] = [
                'id' => $owner->getId(),
                'pseudo' => $owner->getPseudo(),
                'avatar' => $owner->getAvatar()
            ];
        }

        // Jeux spécialisés avec détails
        if (!empty($shop->getSpecializedGames())) {
            $gameIds = $shop->getSpecializedGames();
            $games = $this->gameRepository->findBy(['id' => $gameIds]);
            
            $data['games'] = array_map(function($game) {
                return [
                    'id' => $game->getId(),
                    'name' => $game->getName(),
                    'slug' => $game->getSlug(),
                    'logo' => $game->getLogo(),
                    'primaryColor' => $game->getPrimaryColor()
                ];
            }, $games);
        }

        // Informations détaillées (pour page boutique)
        if ($detailed) {
            $data['openingHours'] = $shop->getOpeningHours();
            $data['siretNumber'] = $shop->getSiretNumber();
            $data['confidenceScore'] = $shop->getConfidenceScore();
            $data['createdAt'] = $shop->getCreatedAt()?->format('Y-m-d H:i:s');
            $data['claimedAt'] = $shop->getClaimedAt()?->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Liste de toutes les boutiques pour les admins
     */
    #[Route('/admin/all', name: 'admin_list', methods: ['GET'])]
    public function getShopsForAdmin(): JsonResponse
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $shops = $this->shopRepository->findAll();
        
        $shopsData = [];
        foreach ($shops as $shop) {
            $shopsData[] = [
                'id' => $shop->getId(),
                'name' => $shop->getName(),
                'type' => $shop->getType(),
                'status' => $shop->getStatus(),
                'address' => $shop->getAddress() ? [
                    'city' => $shop->getAddress()->getCity(),
                    'streetAddress' => $shop->getAddress()->getStreetAddress(),
                    'fullAddress' => $shop->getAddress()->getStreetAddress() . ', ' . $shop->getAddress()->getCity()
                ] : null,
                'owner' => $shop->getOwner() ? [
                    'id' => $shop->getOwner()->getId(),
                    'pseudo' => $shop->getOwner()->getPseudo()
                ] : null,
                'siretNumber' => $shop->getSiretNumber(),
                'phone' => $shop->getPhone(),
                'website' => $shop->getWebsite(),
                'description' => $shop->getDescription(),
                'confidenceScore' => $shop->getConfidenceScore(),
                'isActive' => $shop->isActive(),
                'isFeatured' => $shop->isFeatured()
            ];
        }
        
        return new JsonResponse(['shops' => $shopsData]);
    }

    #[Route('/my-shop', name: 'my_shop', methods: ['GET'])]
    public function getMyShop(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_SHOP', $user->getRoles())) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }
        
        $shop = $this->shopRepository->findOneBy(['owner' => $user]); 
        if (!$shop) {
            return $this->json(['error' => 'Aucune boutique trouvée'], 404);
        }
        
        return $this->json(['shop' => $this->formatShopForApi($shop, true)]);
    }
}