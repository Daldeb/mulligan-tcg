<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\Game;
use App\Entity\GameFormat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * Trouve les decks publics avec filtres optionnels
     */
    public function findPublicDecks(
        ?Game $game = null,
        ?GameFormat $format = null,
        ?string $search = null,
        ?string $archetype = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('d.publishedAt', 'DESC');

        if ($game) {
            $qb->andWhere('d.game = :game')
               ->setParameter('game', $game);
        }

        if ($format) {
            $qb->andWhere('d.gameFormat = :format')
               ->setParameter('format', $format);
        }

        if ($search) {
            $qb->andWhere('d.title LIKE :search OR d.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($archetype) {
            $qb->andWhere('d.archetype = :archetype')
               ->setParameter('archetype', $archetype);
        }

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les decks d'un utilisateur
     */
    public function findUserDecks(User $user, bool $publicOnly = false): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC');

        if ($publicOnly) {
            $qb->andWhere('d.isPublic = :isPublic')
               ->setParameter('isPublic', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les decks populaires par jeu
     */
    public function findPopularDecksByGame(Game $game, int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.game = :game')
            ->andWhere('d.isPublic = :isPublic')
            ->andWhere('d.validDeck = :validDeck')
            ->setParameter('game', $game)
            ->setParameter('isPublic', true)
            ->setParameter('validDeck', true)
            ->orderBy('d.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les decks par archetype
     */
    public function findByArchetype(string $archetype, ?Game $game = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.archetype = :archetype')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('archetype', $archetype)
            ->setParameter('isPublic', true)
            ->orderBy('d.publishedAt', 'DESC');

        if ($game) {
            $qb->andWhere('d.game = :game')
               ->setParameter('game', $game);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les decks publics par jeu
     */
    public function countPublicDecksByGame(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('g.slug as game_slug, g.name as game_name, COUNT(d.id) as deck_count')
            ->join('d.game', 'g')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->groupBy('g.id')
            ->orderBy('deck_count', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Trouve les decks récents par jeu
     */
    public function findRecentDecksByGame(Game $game, int $days = 7, int $limit = 5): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('d')
            ->andWhere('d.game = :game')
            ->andWhere('d.isPublic = :isPublic')
            ->andWhere('d.publishedAt >= :since')
            ->setParameter('game', $game)
            ->setParameter('isPublic', true)
            ->setParameter('since', $since)
            ->orderBy('d.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de decks avec critères avancés
     */
    public function searchDecks(array $criteria): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('isPublic', true);

        if (isset($criteria['games']) && !empty($criteria['games'])) {
            $qb->andWhere('d.game IN (:games)')
               ->setParameter('games', $criteria['games']);
        }

        if (isset($criteria['formats']) && !empty($criteria['formats'])) {
            $qb->andWhere('d.gameFormat IN (:formats)')
               ->setParameter('formats', $criteria['formats']);
        }

        if (isset($criteria['search']) && !empty($criteria['search'])) {
            $qb->andWhere('d.title LIKE :search OR d.description LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (isset($criteria['archetype']) && !empty($criteria['archetype'])) {
            $qb->andWhere('d.archetype = :archetype')
               ->setParameter('archetype', $criteria['archetype']);
        }

        if (isset($criteria['validOnly']) && $criteria['validOnly']) {
            $qb->andWhere('d.validDeck = :validDeck')
               ->setParameter('validDeck', true);
        }

        if (isset($criteria['user']) && $criteria['user'] instanceof User) {
            $qb->andWhere('d.user = :user')
               ->setParameter('user', $criteria['user']);
        }

        // Tri
        $orderBy = $criteria['orderBy'] ?? 'publishedAt';
        $orderDirection = $criteria['orderDirection'] ?? 'DESC';
        $qb->orderBy('d.' . $orderBy, $orderDirection);

        // Pagination
        if (isset($criteria['limit'])) {
            $qb->setMaxResults($criteria['limit']);
        }

        if (isset($criteria['offset'])) {
            $qb->setFirstResult($criteria['offset']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve un deck avec toutes ses cartes (join optimisé)
     */
    public function findDeckWithCards(int $deckId): ?Deck
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.deckCards', 'dc')
            ->leftJoin('dc.hearthstoneCard', 'hc')
            ->leftJoin('dc.pokemonCard', 'pc')
            ->addSelect('dc', 'hc', 'pc')
            ->andWhere('d.id = :id')
            ->setParameter('id', $deckId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre total de decks publics
     */
    public function countPublicDecks(): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les archetypes populaires par jeu
     */
    public function findPopularArchetypes(Game $game, int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.archetype, COUNT(d.id) as deck_count')
            ->andWhere('d.game = :game')
            ->andWhere('d.isPublic = :isPublic')
            ->andWhere('d.archetype IS NOT NULL')
            ->setParameter('game', $game)
            ->setParameter('isPublic', true)
            ->groupBy('d.archetype')
            ->orderBy('deck_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}