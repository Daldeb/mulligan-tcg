<?php

namespace App\Repository\Magic;

use App\Entity\Magic\MagicCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MagicCard>
 */
class MagicCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagicCard::class);
    }

    /**
     * Trouve une carte par son Oracle ID (identifiant unique gameplay)
     */
    public function findByOracleId(string $oracleId): ?MagicCard
    {
        return $this->findOneBy(['oracleId' => $oracleId]);
    }

    /**
     * Trouve une carte par son Scryfall ID
     */
    public function findByScryfallId(string $scryfallId): ?MagicCard
    {
        return $this->findOneBy(['scryfallId' => $scryfallId]);
    }

    /**
     * Récupère toutes les cartes légales en Standard
     */
    public function findStandardLegal(): array
    {
        return $this->findBy(['isStandardLegal' => true], ['name' => 'ASC']);
    }

    /**
     * Récupère toutes les cartes légales en Commander
     */
    public function findCommanderLegal(): array
    {
        return $this->findBy(['isCommanderLegal' => true], ['name' => 'ASC']);
    }

    /**
     * Récupère les cartes légales dans un format spécifique
     */
    public function findByFormat(string $format): array
    {
        $qb = $this->createQueryBuilder('c');

        switch (strtolower($format)) {
            case 'standard':
                $qb->where('c.isStandardLegal = true');
                break;
            case 'commander':
                $qb->where('c.isCommanderLegal = true');
                break;
            case 'both':
                $qb->where('c.isStandardLegal = true AND c.isCommanderLegal = true');
                break;
            default:
                throw new \InvalidArgumentException("Unknown format: {$format}");
        }

        return $qb->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /**
     * Récupère les cartes par couleurs
     */
    public function findByColors(array $colors): array
    {
        $qb = $this->createQueryBuilder('c');

        if (empty($colors)) {
            // Cartes incolores
            $qb->where('c.colors IS NULL OR JSON_LENGTH(c.colors) = 0');
        } else {
            // Cartes contenant au moins une des couleurs spécifiées
            $conditions = [];
            foreach ($colors as $color) {
                $conditions[] = "JSON_CONTAINS(c.colors, '\"$color\"')";
            }
            $qb->where(implode(' OR ', $conditions));
        }

        return $qb->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /**
     * Récupère les cartes par identité de couleur (pour Commander)
     */
    public function findByColorIdentity(array $colorIdentity): array
    {
        $qb = $this->createQueryBuilder('c');

        if (empty($colorIdentity)) {
            // Commandants incolores
            $qb->where('c.colorIdentity IS NULL OR JSON_LENGTH(c.colorIdentity) = 0');
        } else {
            // Cartes dont l'identité de couleur est incluse dans les couleurs spécifiées
            $conditions = [];
            foreach ($colorIdentity as $color) {
                $conditions[] = "JSON_CONTAINS(c.colorIdentity, '\"$color\"')";
            }
            $qb->where(implode(' OR ', $conditions));
        }

        return $qb->andWhere('c.isCommanderLegal = true')
                  ->orderBy('c.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Récupère les cartes par rareté
     */
    public function findByRarity(string $rarity): array
    {
        return $this->findBy(['rarity' => $rarity], ['name' => 'ASC']);
    }

    /**
     * Récupère les cartes par coût de mana converti
     */
    public function findByCmc(float $cmc): array
    {
        return $this->findBy(['cmc' => $cmc], ['name' => 'ASC']);
    }

    /**
     * Récupère les cartes dans une plage de CMC
     */
    public function findByCmcRange(float $minCmc, float $maxCmc): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.cmc >= :minCmc AND c.cmc <= :maxCmc')
            ->setParameter('minCmc', $minCmc)
            ->setParameter('maxCmc', $maxCmc)
            ->orderBy('c.cmc', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche textuelle dans les noms de cartes
     */
    public function searchByName(string $query, ?string $format = null): array
    {
        $qb = $this->createQueryBuilder('c');

        // Recherche dans nom anglais et nom imprimé
        $qb->where('c.name LIKE :query OR c.printedName LIKE :query')
           ->setParameter('query', '%' . $query . '%');

        // Filtrer par format si spécifié
        if ($format) {
            switch (strtolower($format)) {
                case 'standard':
                    $qb->andWhere('c.isStandardLegal = true');
                    break;
                case 'commander':
                    $qb->andWhere('c.isCommanderLegal = true');
                    break;
            }
        }

        return $qb->orderBy('c.name', 'ASC')->getQuery()->getResult();
    }

    /**
     * Récupère les commandants potentiels (légendaires créatures)
     */
    public function findPotentialCommanders(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isCommanderLegal = true')
            ->andWhere('c.typeLine LIKE :legendary OR c.printedTypeLine LIKE :legendary')
            ->andWhere('c.typeLine LIKE :creature OR c.printedTypeLine LIKE :creature')
            ->setParameter('legendary', '%Legendary%')
            ->setParameter('creature', '%Creature%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les cartes par set
     */
    public function findBySet(string $setCode): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.magicSet', 's')
            ->where('s.setCode = :setCode')
            ->setParameter('setCode', strtolower($setCode))
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les cartes par format
     */
    public function countByFormat(): array
    {
        $qb = $this->createQueryBuilder('c');
        
        $result = $qb->select([
                'SUM(CASE WHEN c.isStandardLegal = true THEN 1 ELSE 0 END) as standardCount',
                'SUM(CASE WHEN c.isCommanderLegal = true THEN 1 ELSE 0 END) as commanderCount',
                'SUM(CASE WHEN c.isStandardLegal = true AND c.isCommanderLegal = true THEN 1 ELSE 0 END) as bothFormatsCount',
                'COUNT(c.id) as totalCount'
            ])
            ->getQuery()
            ->getSingleResult();

        return [
            'standard' => (int)$result['standardCount'],
            'commander' => (int)$result['commanderCount'],
            'both_formats' => (int)$result['bothFormatsCount'],
            'total' => (int)$result['totalCount'],
            'standard_only' => (int)$result['standardCount'] - (int)$result['bothFormatsCount'],
            'commander_only' => (int)$result['commanderCount'] - (int)$result['bothFormatsCount']
        ];
    }

    /**
     * Récupère les cartes avec images manquantes
     */
    public function findWithMissingImages(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.imageUrl IS NULL')
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Effectue une recherche avancée avec filtres multiples
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('c');

        if (!empty($criteria['name'])) {
            $qb->andWhere('c.name LIKE :name OR c.printedName LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['format'])) {
            switch (strtolower($criteria['format'])) {
                case 'standard':
                    $qb->andWhere('c.isStandardLegal = true');
                    break;
                case 'commander':
                    $qb->andWhere('c.isCommanderLegal = true');
                    break;
            }
        }

        if (!empty($criteria['colors'])) {
            $colorConditions = [];
            foreach ($criteria['colors'] as $color) {
                $colorConditions[] = "JSON_CONTAINS(c.colors, '\"$color\"')";
            }
            $qb->andWhere(implode(' OR ', $colorConditions));
        }

        if (!empty($criteria['rarity'])) {
            $qb->andWhere('c.rarity = :rarity')
               ->setParameter('rarity', $criteria['rarity']);
        }

        if (isset($criteria['cmc_min'])) {
            $qb->andWhere('c.cmc >= :cmcMin')
               ->setParameter('cmcMin', $criteria['cmc_min']);
        }

        if (isset($criteria['cmc_max'])) {
            $qb->andWhere('c.cmc <= :cmcMax')
               ->setParameter('cmcMax', $criteria['cmc_max']);
        }

        if (!empty($criteria['set'])) {
            $qb->leftJoin('c.magicSet', 's')
               ->andWhere('s.setCode = :setCode')
               ->setParameter('setCode', strtolower($criteria['set']));
        }

        // Tri
        $orderBy = $criteria['order_by'] ?? 'name';
        $orderDirection = $criteria['order_direction'] ?? 'ASC';
        
        switch ($orderBy) {
            case 'cmc':
                $qb->orderBy('c.cmc', $orderDirection)->addOrderBy('c.name', 'ASC');
                break;
            case 'rarity':
                $qb->orderBy('c.rarity', $orderDirection)->addOrderBy('c.name', 'ASC');
                break;
            case 'released_at':
                $qb->orderBy('c.releasedAt', $orderDirection)->addOrderBy('c.name', 'ASC');
                break;
            default:
                $qb->orderBy('c.name', $orderDirection);
        }

        // Limite
        if (!empty($criteria['limit'])) {
            $qb->setMaxResults($criteria['limit']);
        }

        return $qb->getQuery()->getResult();
    }
}