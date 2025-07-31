<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameFormat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameFormat>
 */
class GameFormatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameFormat::class);
    }

    /**
     * Récupère tous les formats actifs triés par jeu et ordre d'affichage
     * 
     * @return GameFormat[]
     */
    public function findActiveFormatsOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.game', 'g')
            ->where('f.isActive = :active')
            ->andWhere('g.isActive = :gameActive')
            ->setParameter('active', true)
            ->setParameter('gameActive', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->addOrderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formats actifs pour un jeu donné
     * 
     * @return GameFormat[]
     */
    public function findActiveFormatsByGame(Game $game): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.game = :game')
            ->andWhere('f.isActive = :active')
            ->setParameter('game', $game)
            ->setParameter('active', true)
            ->orderBy('f.displayOrder', 'ASC')
            ->addOrderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les formats actifs pour plusieurs jeux
     * 
     * @param Game[] $games
     * @return GameFormat[]
     */
    public function findActiveFormatsByGames(array $games): array
    {
        if (empty($games)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->join('f.game', 'g')
            ->where('f.game IN (:games)')
            ->andWhere('f.isActive = :active')
            ->andWhere('g.isActive = :gameActive')
            ->setParameter('games', $games)
            ->setParameter('active', true)
            ->setParameter('gameActive', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un format par son slug et le jeu
     */
    public function findBySlugAndGame(string $slug, Game $game): ?GameFormat
    {
        return $this->createQueryBuilder('f')
            ->where('f.slug = :slug')
            ->andWhere('f.game = :game')
            ->andWhere('f.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('game', $game)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un format par son slug unique (game-slug-format-slug)
     */
    public function findByUniqueSlug(string $gameSlug, string $formatSlug): ?GameFormat
    {
        return $this->createQueryBuilder('f')
            ->join('f.game', 'g')
            ->where('g.slug = :gameSlug')
            ->andWhere('f.slug = :formatSlug')
            ->andWhere('f.isActive = :active')
            ->andWhere('g.isActive = :gameActive')
            ->setParameter('gameSlug', $gameSlug)
            ->setParameter('formatSlug', $formatSlug)
            ->setParameter('active', true)
            ->setParameter('gameActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve plusieurs formats par leurs IDs
     * 
     * @param int[] $formatIds
     * @return GameFormat[]
     */
    public function findByIds(array $formatIds): array
    {
        if (empty($formatIds)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->join('f.game', 'g')
            ->where('f.id IN (:ids)')
            ->andWhere('f.isActive = :active')
            ->andWhere('g.isActive = :gameActive')
            ->setParameter('ids', $formatIds)
            ->setParameter('active', true)
            ->setParameter('gameActive', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un slug existe déjà pour un jeu donné
     */
    public function slugExistsForGame(string $slug, Game $game, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.slug = :slug')
            ->andWhere('f.game = :game')
            ->setParameter('slug', $slug)
            ->setParameter('game', $game);

        if ($excludeId !== null) {
            $qb->andWhere('f.id != :id')
               ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Récupère le prochain ordre d'affichage disponible pour un jeu
     */
    public function getNextDisplayOrderForGame(Game $game): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('MAX(f.displayOrder)')
            ->where('f.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }
}