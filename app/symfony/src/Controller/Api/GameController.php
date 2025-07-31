<?php

namespace App\Controller\Api;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games', name: 'api_games_')]
class GameController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository
    ) {}

    /**
     * Récupère tous les jeux actifs avec leurs formats
     * Utilisé pour le super filtre et les sélecteurs globaux
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $includeFormats = $request->query->getBoolean('include_formats', true);
        
        if ($includeFormats) {
            $games = $this->gameRepository->findActiveGamesWithFormats();
        } else {
            $games = $this->gameRepository->findActiveGamesOrdered();
        }

        $data = array_map(function (Game $game) use ($includeFormats) {
            $gameData = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'description' => $game->getDescription(),
                'primaryColor' => $game->getPrimaryColor(),
                'displayOrder' => $game->getDisplayOrder(),
            ];

            if ($includeFormats) {
                $gameData['formats'] = array_map(function ($format) {
                    return [
                        'id' => $format->getId(),
                        'name' => $format->getName(),
                        'slug' => $format->getSlug(),
                        'description' => $format->getDescription(),
                        'displayOrder' => $format->getDisplayOrder(),
                    ];
                }, $game->getActiveFormats()->toArray());
            }

            return $gameData;
        }, $games);

        return new JsonResponse([
            'success' => true,
            'games' => $data,
            'total' => count($data)
        ]);
    }

    /**
     * Récupère un jeu spécifique par son slug avec ses formats
     */
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $game = $this->gameRepository->findBySlugWithFormats($slug);

        if (!$game) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Jeu non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $game->getId(),
            'name' => $game->getName(),
            'slug' => $game->getSlug(),
            'description' => $game->getDescription(),
            'logo' => $game->getLogo(),
            'primaryColor' => $game->getPrimaryColor(),
            'displayOrder' => $game->getDisplayOrder(),
            'apiConfig' => $game->getApiConfig(),
            'createdAt' => $game->getCreatedAt()->format('c'),
            'updatedAt' => $game->getUpdatedAt()?->format('c'),
            'formats' => array_map(function ($format) {
                return [
                    'id' => $format->getId(),
                    'name' => $format->getName(),
                    'slug' => $format->getSlug(),
                    'description' => $format->getDescription(),
                    'displayOrder' => $format->getDisplayOrder(),
                    'formatConfig' => $format->getFormatConfig(),
                    'uniqueSlug' => $format->getUniqueSlug(),
                    'fullName' => $format->getFullName(),
                ];
            }, $game->getActiveFormats()->toArray())
        ];

        return new JsonResponse([
            'success' => true,
            'game' => $data
        ]);
    }

    /**
     * Récupère les formats d'un jeu spécifique
     */
    #[Route('/{slug}/formats', name: 'formats', methods: ['GET'])]
    public function formats(string $slug): JsonResponse
    {
        $game = $this->gameRepository->findBySlugWithFormats($slug);

        if (!$game) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Jeu non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $formats = array_map(function ($format) {
            return [
                'id' => $format->getId(),
                'name' => $format->getName(),
                'slug' => $format->getSlug(),
                'description' => $format->getDescription(),
                'displayOrder' => $format->getDisplayOrder(),
                'uniqueSlug' => $format->getUniqueSlug(),
                'fullName' => $format->getFullName(),
            ];
        }, $game->getActiveFormats()->toArray());

        return new JsonResponse([
            'success' => true,
            'game' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
            ],
            'formats' => $formats,
            'total' => count($formats)
        ]);
    }

    /**
     * Statistiques globales des jeux
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $games = $this->gameRepository->findActiveGamesWithFormats();
        
        $stats = [
            'totalGames' => count($games),
            'totalFormats' => 0,
            'gameStats' => []
        ];

        foreach ($games as $game) {
            $formatCount = $game->getActiveFormats()->count();
            $stats['totalFormats'] += $formatCount;
            
            $stats['gameStats'][] = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'formatCount' => $formatCount,
                'primaryColor' => $game->getPrimaryColor(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Recherche de jeux par nom ou slug
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse([
                'success' => false,
                'message' => 'La recherche doit contenir au moins 2 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Recherche simple pour le moment - peut être améliorée avec doctrine/search
        $games = $this->gameRepository->createQueryBuilder('g')
            ->where('g.isActive = :active')
            ->andWhere('g.name LIKE :query OR g.slug LIKE :query')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('g.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Game $game) {
            return [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'description' => $game->getDescription(),
                'primaryColor' => $game->getPrimaryColor(),
            ];
        }, $games);

        return new JsonResponse([
            'success' => true,
            'games' => $data,
            'total' => count($data),
            'query' => $query
        ]);
    }
}