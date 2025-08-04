<?php

namespace App\Repository\Magic;

use App\Entity\Magic\MagicSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MagicSet>
 */
class MagicSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagicSet::class);
    }

    /**
     * Trouve un set par son Scryfall ID
     */
    public function findByScryfallId(string $scryfallId): ?MagicSet
    {
        return $this->findOneBy(['scryfallId' => $scryfallId]);
    }

    /**
     * Trouve un set par son code (ex: dft, tdm)
     */
    public function findBySetCode(string $setCode): ?MagicSet
    {
        return $this->findOneBy(['setCode' => strtolower($setCode)]);
    }

    /**
     * Récupère tous les sets principaux (core + expansion)
     */
    public function findMainSets(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.setType IN (:types)')
            ->setParameter('types', ['core', 'expansion', 'draft_innovation'])
            ->orderBy('s.releasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les produits Commander
     */
    public function findCommanderProducts(): array
    {
        return $this->findBy(['setType' => 'commander'], ['releasedAt' => 'DESC']);
    }

    /**
     * Récupère les sets par type
     */
    public function findByType(string $setType): array
    {
        return $this->findBy(['setType' => $setType], ['releasedAt' => 'DESC']);
    }

    /**
     * Récupère les sets sortis dans une période donnée
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.releasedAt >= :startDate AND s.releasedAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.releasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sets les plus récents
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.releasedAt IS NOT NULL')
            ->orderBy('s.releasedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }



    /**
     * Récupère les sets sans cartes importées
     */
    public function findWithoutCards(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.magicCards', 'c')
            ->groupBy('s.id')
            ->having('COUNT(c.id) = 0')
            ->orderBy('s.releasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche textuelle dans les noms de sets
     */
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :query OR s.setCode LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.releasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques globales des sets
     */
    public function getGlobalStats(): array
    {
        $qb = $this->createQueryBuilder('s');
        
        $result = $qb->select([
                'COUNT(s.id) as totalSets',
                'SUM(CASE WHEN s.setType = \'core\' THEN 1 ELSE 0 END) as coreSets',
                'SUM(CASE WHEN s.setType = \'expansion\' THEN 1 ELSE 0 END) as expansionSets',
                'SUM(CASE WHEN s.setType = \'commander\' THEN 1 ELSE 0 END) as commanderSets'
            ])
            ->getQuery()
            ->getSingleResult();

        // Compter les cartes réellement importées
        $importedCards = $this->getEntityManager()
            ->createQuery('SELECT COUNT(c.id) FROM App\Entity\Magic\MagicCard c')
            ->getSingleScalarResult();

        return [
            'total_sets' => (int)$result['totalSets'],
            'total_imported_cards' => (int)$importedCards,
            'core_sets' => (int)$result['coreSets'],
            'expansion_sets' => (int)$result['expansionSets'],
            'commander_sets' => (int)$result['commanderSets']
        ];
    }

    /**
     * Récupère les sets avec leur progression d'import
     */
    public function findWithCardCounts(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.magicCards', 'c')
            ->addSelect('COUNT(c.id) as cardCount')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sets récemment synchronisés
     */
    public function findRecentlySynced(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.lastSyncedAt IS NOT NULL')
            ->orderBy('s.lastSyncedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sets qui nécessitent une synchronisation
     */
    public function findNeedingSync(?\DateInterval $maxAge = null): array
    {
        $qb = $this->createQueryBuilder('s');
        
        if ($maxAge !== null) {
            $cutoffDate = (new \DateTimeImmutable())->sub($maxAge);
            $qb->where('s.lastSyncedAt IS NULL OR s.lastSyncedAt < :cutoffDate')
               ->setParameter('cutoffDate', $cutoffDate);
        } else {
            $qb->where('s.lastSyncedAt IS NULL');
        }

        return $qb->orderBy('s.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Filtre les sets par critères multiples
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($criteria['name'])) {
            $qb->andWhere('s.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['type'])) {
            $qb->andWhere('s.setType = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (!empty($criteria['year'])) {
            $qb->andWhere('YEAR(s.releasedAt) = :year')
               ->setParameter('year', $criteria['year']);
        }

        if (isset($criteria['digital_only'])) {
            $qb->andWhere('s.isDigitalOnly = :digitalOnly')
               ->setParameter('digitalOnly', $criteria['digital_only']);
        }

        // Tri
        $orderBy = $criteria['order_by'] ?? 'releasedAt';
        $orderDirection = $criteria['order_direction'] ?? 'DESC';
        
        $qb->orderBy('s.' . $orderBy, $orderDirection);

        // Limite
        if (!empty($criteria['limit'])) {
            $qb->setMaxResults($criteria['limit']);
        }

        return $qb->getQuery()->getResult();
    }
}