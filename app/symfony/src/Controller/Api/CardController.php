<?php

namespace App\Controller\Api;

use App\Repository\GameRepository;
use App\Repository\Hearthstone\HearthstoneCardRepository;
use App\Repository\Pokemon\PokemonCardRepository;
use App\Repository\Magic\MagicCardRepository;
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
        private MagicCardRepository $magicCardRepository,
    ) {}

    #[Route('/{gameSlug}', name: 'by_game', methods: ['GET'])]
    public function getCardsByGame(string $gameSlug, Request $request): JsonResponse
    {
        // Vérifier que le jeu existe
        $game = $this->gameRepository->findOneBy(['slug' => $gameSlug]);
        if (!$game) {
            return $this->json(['error' => 'Jeu introuvable'], 404);
        }

        $limit = min($request->query->getInt('limit', 2500), 4000);
        $format = $request->query->get('format', 'standard');

        switch ($gameSlug) {
            case 'hearthstone':
                $cards = $this->getHearthstoneCards($limit, $format);
                break;
            case 'pokemon':
                $cards = $this->getPokemonCards($limit, $format);
                break;
            case 'magic':
                $cards = $this->getMagicCards($limit, $format);
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

    private function getPokemonCards(int $limit, string $format): array
    {
        // TODO: Implémenter plus tard
        return [];
    }

    private function getMagicCards(int $limit, string $format): array
    {
        $qb = $this->magicCardRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.magicSet', 's')
            ->addSelect('s')
            ->where('1 = 1'); // Toutes les cartes Magic sont "collectibles"

        // Filtrer selon le format Magic
        if ($format === 'standard') {
            $qb->andWhere('c.isStandardLegal = true');
        } elseif ($format === 'commander') {
            $qb->andWhere('c.isCommanderLegal = true');
        }

        // Exclure les cartes digitales et tokens par défaut
        $qb->andWhere('c.isDigital = false')
           ->andWhere('c.isBooster = true');

        $cards = $qb->setMaxResults($limit)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map([$this, 'serializeMagicCard'], $cards);
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

    private function serializeMagicCard(\App\Entity\Magic\MagicCard $card): array
    {
        return [
            'id' => $card->getId(),
            'name' => $card->getDisplayName(),
            'manaCost' => $card->getManaCost(),
            'cmc' => $card->getCmc(),
            'power' => $card->getPower(),
            'toughness' => $card->getToughness(),
            'rarity' => strtolower($card->getRarity() ?? 'common'),
            'typeLine' => $card->getDisplayTypeLine(),
            'cardType' => $this->extractMainCardType($card->getDisplayTypeLine()),
            'colors' => $card->getColors() ?? [],
            'colorIdentity' => $card->getColorIdentity() ?? [],
            'text' => $card->getDisplayText(),
            'imageUrl' => $card->getImageUrl(),
            'isStandardLegal' => $card->isStandardLegal(),
            'isCommanderLegal' => $card->isCommanderLegal(),
            'isCreature' => $card->isCreature(),
            'isLand' => $card->isLand(),
            'isLegendary' => $card->isLegendary(),
            'canBeCommander' => $card->canBeCommander(),
            'set' => [
                'id' => $card->getMagicSet()->getId(),
                'name' => $card->getMagicSet()->getName(),
                'code' => strtoupper($card->getMagicSet()->getSetCode())
            ]
        ];
    }

    /**
     * Extrait le type principal d'une carte Magic depuis sa typeLine
     * CORRECTION : Utilise la logique LIKE au lieu du premier mot
     */
    private function extractMainCardType(string $typeLine): string
    {
        $typeLine = strtolower($typeLine);
        
        // Gestion des termes français ET anglais
        if (strpos($typeLine, 'creature') !== false || strpos($typeLine, 'créature') !== false) return 'creature';
        if (strpos($typeLine, 'planeswalker') !== false) return 'planeswalker';
        if (strpos($typeLine, 'land') !== false || strpos($typeLine, 'terrain') !== false) return 'land';
        if (strpos($typeLine, 'artifact') !== false || strpos($typeLine, 'artefact') !== false) return 'artifact';
        if (strpos($typeLine, 'enchantment') !== false || strpos($typeLine, 'enchantement') !== false) return 'enchantment';
        if (strpos($typeLine, 'instant') !== false || strpos($typeLine, 'éphémère') !== false) return 'instant';
        if (strpos($typeLine, 'sorcery') !== false || strpos($typeLine, 'rituel') !== false) return 'sorcery';
        if (strpos($typeLine, 'battle') !== false || strpos($typeLine, 'bataille') !== false) return 'battle';
        
        return 'other';
    }
}