<?php

namespace App\Repository\Hearthstone;

use App\Entity\Hearthstone\HearthstoneCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HearthstoneCard>
 */
class HearthstoneCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HearthstoneCard::class);
    }

    /**
     * Trouve une carte par son external_id
     */
    public function findByExternalId(string $externalId): ?HearthstoneCard
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.externalId = :external_id')
            ->setParameter('external_id', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * CRUCIAL: Trouve une carte par son dbfId (pour deckcode mapping)
     */
    public function findByDbfId(int $dbfId): ?HearthstoneCard
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.dbfId = :dbf_id')
            ->setParameter('dbf_id', $dbfId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les cartes d'un set spécifique
     */
    public function findBySet(int $setId): array
    {
        return $this->createQueryBuilder('hc')
            ->innerJoin('hc.hearthstoneSet', 'hs')
            ->andWhere('hs.id = :set_id')
            ->setParameter('set_id', $setId)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom (pour autocomplete)
     */
    public function findByNameLike(string $name, int $limit = 20): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('hc.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre par classe
     */
    public function findByCardClass(string $cardClass): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.cardClass = :card_class')
            ->setParameter('card_class', $cardClass)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre par type (MINION, SPELL, WEAPON, etc.)
     */
    public function findByCardType(string $cardType): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.cardType = :card_type')
            ->setParameter('card_type', $cardType)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre par coût de mana
     */
    public function findByCostRange(int $minCost, int $maxCost): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.cost >= :min_cost')
            ->andWhere('hc.cost <= :max_cost')
            ->setParameter('min_cost', $minCost)
            ->setParameter('max_cost', $maxCost)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les cartes légales en Standard
     */
    public function findStandardLegal(): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.isStandardLegal = :standard_legal')
            ->setParameter('standard_legal', true)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère uniquement les cartes collectionnables
     */
    public function findCollectible(): array
    {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.isCollectible = :collectible')
            ->setParameter('collectible', true)
            ->orderBy('hc.cost', 'ASC')
            ->addOrderBy('hc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée combinée
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('hc');

        if (isset($filters['name']) && !empty($filters['name'])) {
            $qb->andWhere('hc.name LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (isset($filters['cardClass']) && !empty($filters['cardClass'])) {
            $qb->andWhere('hc.cardClass = :card_class')
               ->setParameter('card_class', $filters['cardClass']);
        }

        if (isset($filters['cardType']) && !empty($filters['cardType'])) {
            $qb->andWhere('hc.cardType = :card_type')
               ->setParameter('card_type', $filters['cardType']);
        }

        if (isset($filters['minCost']) && is_numeric($filters['minCost'])) {
            $qb->andWhere('hc.cost >= :min_cost')
               ->setParameter('min_cost', (int)$filters['minCost']);
        }

        if (isset($filters['maxCost']) && is_numeric($filters['maxCost'])) {
            $qb->andWhere('hc.cost <= :max_cost')
               ->setParameter('max_cost', (int)$filters['maxCost']);
        }

        if (isset($filters['rarity']) && !empty($filters['rarity'])) {
            $qb->andWhere('hc.rarity = :rarity')
               ->setParameter('rarity', $filters['rarity']);
        }

        if (isset($filters['standardLegal']) && $filters['standardLegal'] === true) {
            $qb->andWhere('hc.isStandardLegal = :standard_legal')
               ->setParameter('standard_legal', true);
        }

        return $qb->orderBy('hc.cost', 'ASC')
                  ->addOrderBy('hc.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte le nombre total de cartes
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('hc')
            ->select('COUNT(hc.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte par rareté
     */
    public function countByRarity(): array
    {
        return $this->createQueryBuilder('hc')
            ->select('hc.rarity, COUNT(hc.id) as count')
            ->groupBy('hc.rarity')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(HearthstoneCard $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HearthstoneCard $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}