<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Récupère tous les jeux actifs triés par ordre d'affichage
     * 
     * @return Game[]
     */
    public function findActiveGamesOrdered(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les jeux actifs avec leurs formats actifs
     * 
     * @return Game[]
     */
    public function findActiveGamesWithFormats(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.formats', 'f')
            ->addSelect('f')
            ->where('g.isActive = :active')
            ->andWhere('f.isActive = :formatActive OR f.id IS NULL')
            ->setParameter('active', true)
            ->setParameter('formatActive', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un jeu par son slug
     */
    public function findBySlug(string $slug): ?Game
    {
        return $this->createQueryBuilder('g')
            ->where('g.slug = :slug')
            ->andWhere('g.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un jeu par son slug avec ses formats
     */
    public function findBySlugWithFormats(string $slug): ?Game
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.formats', 'f')
            ->addSelect('f')
            ->where('g.slug = :slug')
            ->andWhere('g.isActive = :active')
            ->andWhere('f.isActive = :formatActive OR f.id IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->setParameter('formatActive', true)
            ->orderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve plusieurs jeux par leurs IDs
     * 
     * @param int[] $gameIds
     * @return Game[]
     */
    public function findByIds(array $gameIds): array
    {
        if (empty($gameIds)) {
            return [];
        }

        return $this->createQueryBuilder('g')
            ->where('g.id IN (:ids)')
            ->andWhere('g.isActive = :active')
            ->setParameter('ids', $gameIds)
            ->setParameter('active', true)
            ->orderBy('g.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un slug existe déjà
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('g.id != :id')
               ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Récupère le prochain ordre d'affichage disponible
     */
    public function getNextDisplayOrder(): int
    {
        $result = $this->createQueryBuilder('g')
            ->select('MAX(g.displayOrder)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }
}