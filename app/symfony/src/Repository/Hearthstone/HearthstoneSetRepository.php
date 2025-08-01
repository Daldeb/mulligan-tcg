<?php

namespace App\Repository\Hearthstone;

use App\Entity\Hearthstone\HearthstoneSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HearthstoneSet>
 */
class HearthstoneSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HearthstoneSet::class);
    }

    /**
     * Trouve un set par son external_id
     */
    public function findByExternalId(string $externalId): ?HearthstoneSet
    {
        return $this->createQueryBuilder('hs')
            ->andWhere('hs.externalId = :external_id')
            ->setParameter('external_id', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les sets avec le nombre de cartes
     */
    public function findAllWithCardCount(): array
    {
        return $this->createQueryBuilder('hs')
            ->leftJoin('hs.cards', 'hc')
            ->addSelect('COUNT(hc.id) as cardCount')
            ->groupBy('hs.id')
            ->orderBy('hs.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sets d'un jeu spécifique
     */
    public function findByGame(int $gameId): array
    {
        return $this->createQueryBuilder('hs')
            ->innerJoin('hs.game', 'g')
            ->andWhere('g.id = :game_id')
            ->setParameter('game_id', $gameId)
            ->orderBy('hs.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sets récents (derniers ajoutés)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('hs')
            ->orderBy('hs.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de sets
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('hs')
            ->select('COUNT(hs.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(HearthstoneSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HearthstoneSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}