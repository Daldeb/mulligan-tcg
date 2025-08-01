<?php

namespace App\Repository;

use App\Entity\DeckCard;
use App\Entity\Deck;
use App\Entity\GameFormat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeckCard>
 */
class DeckCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeckCard::class);
    }

    /**
     * Trouve les cartes d'un deck avec leurs données complètes
     */
    public function findDeckCardsWithDetails(Deck $deck): array
    {
        return $this->createQueryBuilder('dc')
            ->leftJoin('dc.hearthstoneCard', 'hc')
            ->leftJoin('dc.pokemonCard', 'pc')
            ->leftJoin('hc.hearthstoneSet', 'hs')
            ->leftJoin('pc.pokemonSet', 'ps')
            ->addSelect('hc', 'pc', 'hs', 'ps')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une carte spécifique dans un deck
     */
    public function findCardInDeck(Deck $deck, int $cardId, string $cardType): ?DeckCard
    {
        $qb = $this->createQueryBuilder('dc')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck);

        switch ($cardType) {
            case 'hearthstone':
                $qb->andWhere('dc.hearthstoneCard = :cardId');
                break;
            case 'pokemon':
                $qb->andWhere('dc.pokemonCard = :cardId');
                break;
            default:
                return null;
        }

        $qb->setParameter('cardId', $cardId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Supprime toutes les cartes d'un deck
     */
    public function removeAllCardsFromDeck(Deck $deck): void
    {
        $this->createQueryBuilder('dc')
            ->delete()
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->execute();
    }

    /**
     * Calcule les statistiques d'un deck
     */
    public function getDeckStats(Deck $deck): array
    {
        $result = $this->createQueryBuilder('dc')
            ->select('
                SUM(dc.quantity) as totalCards,
                COUNT(dc.id) as uniqueCards
            ')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'totalCards' => (int)($result['totalCards'] ?? 0),
            'uniqueCards' => (int)($result['uniqueCards'] ?? 0)
        ];
    }

    /**
     * Obtient la répartition par coût de mana (Hearthstone)
     */
    public function getManaCurve(Deck $deck): array
    {
        if ($deck->getGame()->getSlug() !== 'hearthstone') {
            return [];
        }

        $result = $this->createQueryBuilder('dc')
            ->select('hc.cost, SUM(dc.quantity) as count')
            ->join('dc.hearthstoneCard', 'hc')
            ->andWhere('dc.deck = :deck')
            ->andWhere('hc.cost IS NOT NULL')
            ->setParameter('deck', $deck)
            ->groupBy('hc.cost')
            ->orderBy('hc.cost', 'ASC')
            ->getQuery()
            ->getResult();

        $manaCurve = [];
        foreach ($result as $row) {
            $manaCurve[$row['cost']] = (int)$row['count'];
        }

        return $manaCurve;
    }

    /**
     * Obtient la répartition par rareté
     */
    public function getRarityDistribution(Deck $deck): array
    {
        $gameSlug = $deck->getGame()->getSlug();
        
        $qb = $this->createQueryBuilder('dc')
            ->select('card.rarity, SUM(dc.quantity) as count')
            ->andWhere('dc.deck = :deck')
            ->setParameter('deck', $deck)
            ->groupBy('card.rarity')
            ->orderBy('count', 'DESC');

        if ($gameSlug === 'hearthstone') {
            $qb->join('dc.hearthstoneCard', 'card');
        } elseif ($gameSlug === 'pokemon') {
            $qb->join('dc.pokemonCard', 'card');
        } else {
            return [];
        }

        $result = $qb->getQuery()->getResult();

        $distribution = [];
        foreach ($result as $row) {
            $rarity = $row['rarity'] ?? 'UNKNOWN';
            $distribution[$rarity] = (int)$row['count'];
        }

        return $distribution;
    }

    /**
     * Obtient les cartes les plus utilisées dans un format
     */
    public function getMostUsedCardsInFormat(GameFormat $format, int $limit = 20): array
    {
        $gameSlug = $format->getGame()->getSlug();
        
        $qb = $this->createQueryBuilder('dc')
            ->select('card.name, card.id, SUM(dc.quantity) as usage_count, COUNT(DISTINCT dc.deck) as deck_count')
            ->join('dc.deck', 'd')
            ->andWhere('d.gameFormat = :format')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('format', $format)
            ->setParameter('isPublic', true)
            ->groupBy('card.id')
            ->orderBy('usage_count', 'DESC')
            ->setMaxResults($limit);

        if ($gameSlug === 'hearthstone') {
            $qb->join('dc.hearthstoneCard', 'card');
        } elseif ($gameSlug === 'pokemon') {
            $qb->join('dc.pokemonCard', 'card');
        } else {
            return [];
        }

        return $qb->getQuery()->getResult();
    }
}