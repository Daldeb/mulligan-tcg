<?php

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Shop>
 */
class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    /**
     * Sauvegarde une boutique
     */
    public function save(Shop $shop, bool $flush = false): void
    {
        $shop->updateTimestamp();
        $this->getEntityManager()->persist($shop);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une boutique
     */
    public function remove(Shop $shop, bool $flush = false): void
    {
        $this->getEntityManager()->remove($shop);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ============= REQUÊTES DE BASE =============

    /**
     * Trouve une boutique par son slug
     */
    public function findBySlug(string $slug): ?Shop
    {
        return $this->findOneBy(['slug' => $slug, 'isActive' => true]);
    }

    /**
     * Trouve toutes les boutiques actives
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les boutiques par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->andWhere('s.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les boutiques par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.isActive = :active')
            ->setParameter('status', $status)
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ============= REQUÊTES GÉOGRAPHIQUES =============

    /**
     * Trouve les boutiques dans un rayon donné (en km)
     * Retourne un tableau de ['shop' => Shop, 'distance' => float]
     */
    public function findNearby(float $latitude, float $longitude, float $radiusKm = 50): array
    {
        // Utilise la formule haversine pour calculer la distance
        $sql = "
            SELECT s.*, a.latitude, a.longitude,
                   (6371 * acos(cos(radians(:lat)) * cos(radians(a.latitude)) 
                   * cos(radians(a.longitude) - radians(:lng)) 
                   + sin(radians(:lat)) * sin(radians(a.latitude)))) AS distance
            FROM shop s
            INNER JOIN addresses a ON s.address_id = a.id
            WHERE s.is_active = 1
            HAVING distance < :radius
            ORDER BY distance ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, [
            'lat' => $latitude,
            'lng' => $longitude,
            'radius' => $radiusKm
        ]);

        $shops = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $shop = $this->find($row['id']);
            if ($shop) {
                $shops[] = [
                    'shop' => $shop,
                    'distance' => (float) $row['distance']
                ];
            }
        }

        return $shops;
    }

    /**
     * Trouve les boutiques par département
     */
    public function findByDepartment(string $department): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.address', 'a')
            ->where('a.postalCode LIKE :department')
            ->andWhere('s.isActive = :active')
            ->setParameter('department', $department . '%')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les boutiques par ville
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.address', 'a')
            ->where('LOWER(a.city) LIKE LOWER(:city)')
            ->andWhere('s.isActive = :active')
            ->setParameter('city', '%' . $city . '%')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ============= REQUÊTES PAR JEUX ET SERVICES =============

    /**
     * Trouve les boutiques spécialisées dans un jeu - VERSION CORRIGÉE
     */
    public function findBySpecializedGame(int $gameId): array
    {
        // Utilisation de SQL natif pour JSON_CONTAINS
        $sql = "
            SELECT s.* FROM shop s 
            WHERE s.is_active = 1 
            AND JSON_CONTAINS(s.specialized_games, :gameId) = 1
            ORDER BY s.name ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, [
            'gameId' => json_encode($gameId)
        ]);

        $shops = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $shop = $this->find($row['id']);
            if ($shop) {
                $shops[] = $shop;
            }
        }

        return $shops;
    }

    /**
     * Trouve les boutiques proposant un service spécifique - VERSION CORRIGÉE
     */
    public function findByService(string $service): array
    {
        // Utilisation de SQL natif pour JSON_CONTAINS
        $sql = "
            SELECT s.* FROM shop s 
            WHERE s.is_active = 1 
            AND JSON_CONTAINS(s.services, :service) = 1
            ORDER BY s.name ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, [
            'service' => json_encode($service)
        ]);

        $shops = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $shop = $this->find($row['id']);
            if ($shop) {
                $shops[] = $shop;
            }
        }

        return $shops;
    }

    /**
     * Recherche flexible par nom, ville, services
     */
    public function search(string $query): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.address', 'a')
            ->where('s.isActive = :active')
            ->setParameter('active', true);

        // Recherche dans le nom de la boutique
        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(s.name) LIKE LOWER(:query)',
                'LOWER(a.city) LIKE LOWER(:query)',
                'LOWER(s.description) LIKE LOWER(:query)'
            )
        );

        $qb->setParameter('query', '%' . $query . '%')
           ->orderBy('s.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    // ============= REQUÊTES POUR STATISTIQUES =============

    /**
     * Trouve les boutiques les plus populaires - VERSION SIMPLIFIÉE SANS JSON_EXTRACT
     */
    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->andWhere('s.isFeatured = :featured')
            ->setParameter('active', true)
            ->setParameter('featured', true)
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les boutiques les mieux notées - VERSION CORRIGÉE
     */
    public function findTopRated(int $limit = 10): array
    {
        // Utilisation de SQL natif pour JSON_EXTRACT
        $sql = "
            SELECT s.* FROM shop s 
            WHERE s.is_active = 1 
            AND JSON_EXTRACT(s.stats, '$.rating') > 0
            AND JSON_EXTRACT(s.stats, '$.reviews_count') >= 5
            ORDER BY JSON_EXTRACT(s.stats, '$.rating') DESC,
                     JSON_EXTRACT(s.stats, '$.reviews_count') DESC
            LIMIT :limit
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql, ['limit' => $limit]);

        $shops = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $shop = $this->find($row['id']);
            if ($shop) {
                $shops[] = $shop;
            }
        }

        return $shops;
    }

    /**
     * Statistiques générales
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('
                COUNT(s.id) as totalShops,
                SUM(CASE WHEN s.type = :scraped THEN 1 ELSE 0 END) as scrapedShops,
                SUM(CASE WHEN s.type = :registered THEN 1 ELSE 0 END) as registeredShops,
                SUM(CASE WHEN s.type = :verified THEN 1 ELSE 0 END) as verifiedShops,
                SUM(CASE WHEN s.owner IS NOT NULL THEN 1 ELSE 0 END) as claimedShops
            ')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->setParameter('scraped', Shop::TYPE_SCRAPED)
            ->setParameter('registered', Shop::TYPE_REGISTERED)
            ->setParameter('verified', Shop::TYPE_VERIFIED);

        return $qb->getQuery()->getSingleResult();
    }

    // ============= REQUÊTES D'ADMINISTRATION =============

    /**
     * Trouve les boutiques non revendiquées (pour scrapping)
     */
    public function findUnclaimedScraped(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->andWhere('s.owner IS NULL')
            ->setParameter('type', Shop::TYPE_SCRAPED)
            ->orderBy('s.confidenceScore', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les boutiques nécessitant une vérification
     */
    public function findNeedingVerification(): array
    {
        $oneWeekAgo = new \DateTimeImmutable('-1 week');

        return $this->createQueryBuilder('s')
            ->where('s.status = :pending OR s.lastVerifiedAt < :weekAgo')
            ->setParameter('pending', Shop::STATUS_PENDING)
            ->setParameter('weekAgo', $oneWeekAgo)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Query builder de base pour l'admin
     */
    public function createAdminQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.owner', 'u')
            ->leftJoin('s.address', 'a')
            ->addSelect('u', 'a');
    }

    // ============= REQUÊTES POUR API/CARTE =============

    /**
     * Données optimisées pour la carte - VERSION CORRIGÉE
     */
    public function findForMap(): array
    {
        // Utilisation de SQL natif pour JSON_EXTRACT
        $sql = "
            SELECT 
                s.id, s.name, s.slug, s.type, s.logo, s.primary_color,
                JSON_EXTRACT(s.stats, '$.rating') as rating,
                JSON_EXTRACT(s.stats, '$.reviews_count') as reviewsCount,
                a.latitude, a.longitude, a.city, a.postal_code
            FROM shop s
            INNER JOIN addresses a ON s.address_id = a.id
            WHERE s.is_active = 1
            AND a.latitude IS NOT NULL
            AND a.longitude IS NOT NULL
            ORDER BY s.is_featured DESC, s.display_order ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery($sql);

        return $result->fetchAllAssociative();
    }

    /**
     * Données complètes pour l'API - VERSION CORRIGÉE
     */
    public function findForApi(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.address', 'a')
            ->addSelect('a')
            ->where('s.isActive = :active')
            ->setParameter('active', true);

        // Filtres optionnels
        if (!empty($filters['type'])) {
            $qb->andWhere('s.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['department'])) {
            $qb->andWhere('a.postalCode LIKE :department')
               ->setParameter('department', $filters['department'] . '%');
        }

        // Pour les filtres JSON, on utilise une requête séparée si nécessaire
        $shops = $qb->orderBy('s.isFeatured', 'DESC')
                   ->addOrderBy('s.displayOrder', 'ASC')
                   ->addOrderBy('s.name', 'ASC')
                   ->getQuery()
                   ->getResult();

        // Filtrage post-requête pour les champs JSON
        if (!empty($filters['game'])) {
            $gameId = (int) $filters['game'];
            $shops = array_filter($shops, function(Shop $shop) use ($gameId) {
                return $shop->isSpecializedInGame($gameId);
            });
        }

        if (!empty($filters['service'])) {
            $service = $filters['service'];
            $shops = array_filter($shops, function(Shop $shop) use ($service) {
                return $shop->hasService($service);
            });
        }

        return array_values($shops);
    }
}