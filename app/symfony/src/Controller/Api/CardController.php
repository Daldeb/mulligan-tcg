<?php

namespace App\Controller\Api;

use App\Repository\GameRepository;
use App\Repository\Hearthstone\HearthstoneCardRepository;
use App\Repository\Pokemon\PokemonCardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cards', name: 'api_cards_')]
class CardController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private HearthstoneCardRepository $hearthstoneCardRepository,
        private PokemonCardRepository $pokemonCardRepository,
    ) {}

    #[Route('/{gameSlug}', name: 'by_game', methods: ['GET'])]
    public function getCardsByGame(string $gameSlug, Request $request): JsonResponse
    {
        // Vérifier que le jeu existe
        $game = $this->gameRepository->findOneBy(['slug' => $gameSlug]);
        if (!$game) {
            return $this->json(['error' => 'Jeu introuvable'], 404);
        }

        $limit = min($request->query->getInt('limit', 2500), 4000); // ← Plus de cartes par défaut
        $format = $request->query->get('format', 'standard'); // ← Récupérer le format

        switch ($gameSlug) {
            case 'hearthstone':
                $cards = $this->getHearthstoneCards($limit, $format); // ← Passer le format
                break;
            case 'pokemon':
                $cards = $this->getPokemonCards($limit, $format);
                break;
            default:
                return $this->json(['error' => 'Jeu non supporté'], 400);
        }

        return $this->json($cards);
    }

    private function getHearthstoneCards(int $limit, string $format): array
    {
        $qb = $this->hearthstoneCardRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.hearthstoneSet', 's')
            ->addSelect('s')
            ->where('c.isCollectible = true');

        // Filtrer selon le format
        if ($format === 'standard') {
            $qb->andWhere('c.isStandardLegal = true');
        } elseif ($format === 'wild') {
            $qb->andWhere('c.isWildLegal = true');
        }

        $cards = $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map([$this, 'serializeHearthstoneCard'], $cards);
    }

    private function getPokemonCards(int $limit): array
    {
        // TODO: Implémenter plus tard
        return [];
    }

    private function serializeHearthstoneCard(\App\Entity\Hearthstone\HearthstoneCard $card): array
    {
        return [
            'id' => $card->getId(),
            'name' => $card->getName(),
            'cost' => $card->getCost(),
            'attack' => $card->getAttack(),
            'health' => $card->getHealth(),
            'rarity' => strtolower($card->getRarity() ?? 'common'),
            'cardType' => strtolower($card->getCardType() ?? 'minion'),
            'cardClass' => strtolower($card->getCardClass() ?? 'neutral'),
            'text' => $card->getText(),
            'imageUrl' => $card->getImageUrl(),
            'isStandardLegal' => $card->isStandardLegal(),
            'isWildLegal' => $card->isWildLegal(),
        ];
    }
}