<?php

namespace App\Controller\Api;

use App\Repository\GameRepository;
use App\Repository\GameFormatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class GameController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private GameFormatRepository $gameFormatRepository
    ) {}

    /**
     * Récupère la liste des jeux actifs
     */
    #[Route('/games', name: 'api_games_list', methods: ['GET'])]
    public function getGames(): JsonResponse
    {
        $games = $this->gameRepository->findBy(
            ['isActive' => true],
            ['displayOrder' => 'ASC', 'name' => 'ASC']
        );

        $data = [];
        foreach ($games as $game) {
            $data[] = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'description' => $game->getDescription(),
                'logo' => $game->getLogo(),
                'primaryColor' => $game->getPrimaryColor(),
                'isActive' => $game->isActive(),
                'displayOrder' => $game->getDisplayOrder()
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Récupère un jeu spécifique par ID
     */
    #[Route('/games/{id}', name: 'api_games_show', methods: ['GET'])]
    public function getGame(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json([
                'success' => false,
                'message' => 'Jeu non trouvé'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'description' => $game->getDescription(),
                'logo' => $game->getLogo(),
                'primaryColor' => $game->getPrimaryColor(),
                'isActive' => $game->isActive(),
                'displayOrder' => $game->getDisplayOrder(),
                'apiConfig' => $game->getApiConfig()
            ]
        ]);
    }

    /**
     * Récupère les formats d'un jeu spécifique
     */
    #[Route('/games/{id}/formats', name: 'api_games_formats', methods: ['GET'])]
    public function getGameFormats(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json([
                'success' => false,
                'message' => 'Jeu non trouvé'
            ], 404);
        }

        $formats = $this->gameFormatRepository->findBy(
            [
                'game' => $game,
                'isActive' => true
            ],
            ['displayOrder' => 'ASC', 'name' => 'ASC']
        );

        $data = [];
        foreach ($formats as $format) {
            $data[] = [
                'id' => $format->getId(),
                'name' => $format->getName(),
                'slug' => $format->getSlug(),
                'description' => $format->getDescription(),
                'isActive' => $format->isActive(),
                'displayOrder' => $format->getDisplayOrder(),
                'formatConfig' => $format->getFormatConfig(),
                'gameId' => $game->getId(),
                'gameName' => $game->getName(),
                'gameSlug' => $game->getSlug()
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'game' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug()
            ]
        ]);
    }

    /**
     * Récupère tous les formats de tous les jeux (pour navigation globale)
     */
    #[Route('/formats', name: 'api_formats_list', methods: ['GET'])]
    public function getAllFormats(): JsonResponse
    {
        $formats = $this->gameFormatRepository->createQueryBuilder('f')
            ->join('f.game', 'g')
            ->andWhere('f.isActive = :formatActive')
            ->andWhere('g.isActive = :gameActive')
            ->setParameter('formatActive', true)
            ->setParameter('gameActive', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($formats as $format) {
            $data[] = [
                'id' => $format->getId(),
                'name' => $format->getName(),
                'slug' => $format->getSlug(),
                'description' => $format->getDescription(),
                'fullName' => $format->getFullName(),
                'uniqueSlug' => $format->getUniqueSlug(),
                'game' => [
                    'id' => $format->getGame()->getId(),
                    'name' => $format->getGame()->getName(),
                    'slug' => $format->getGame()->getSlug(),
                    'primaryColor' => $format->getGame()->getPrimaryColor()
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }
}