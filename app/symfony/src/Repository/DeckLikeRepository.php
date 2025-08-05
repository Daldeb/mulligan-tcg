<?php

namespace App\Repository;

use App\Entity\DeckLike;
use App\Entity\Deck;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeckLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeckLike::class);
    }

    /**
     * Vérifie si un utilisateur a liké un deck
     */
    public function hasUserLikedDeck(User $user, Deck $deck): bool
    {
        return $this->createQueryBuilder('dl')
            ->select('COUNT(dl.id)')
            ->andWhere('dl.user = :user')
            ->andWhere('dl.deck = :deck')
            ->setParameter('user', $user)
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Trouve le like d'un utilisateur pour un deck
     */
    public function findUserLike(User $user, Deck $deck): ?DeckLike
    {
        return $this->createQueryBuilder('dl')
            ->andWhere('dl.user = :user')
            ->andWhere('dl.deck = :deck')
            ->setParameter('user', $user)
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre total de likes pour un deck
     */
    public function countLikesForDeck(Deck $deck): int
    {
        return $this->createQueryBuilder('dl')
            ->select('COUNT(dl.id)')
            ->andWhere('dl.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les decks les plus likés
     */
    public function findMostLikedDecks(int $limit = 10): array
    {
        return $this->createQueryBuilder('dl')
            ->select('IDENTITY(dl.deck) as deck_id, COUNT(dl.id) as likes_count')
            ->groupBy('dl.deck')
            ->orderBy('likes_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}