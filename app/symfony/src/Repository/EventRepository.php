<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Trouve les événements visibles pour les utilisateurs publics
     */
    public function findVisibleEvents(int $limit = 20): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :approved')
            ->andWhere('e.visibility = :visible')
            ->setParameter('approved', Event::STATUS_APPROVED)
            ->setParameter('visible', Event::VISIBILITY_VISIBLE)
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements en attente de validation admin
     */
    public function findPendingReview(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :pending')
            ->setParameter('pending', Event::STATUS_PENDING_REVIEW)
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements à venir (approuvés et visibles)
     */
    public function findUpcoming(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :approved')
            ->andWhere('e.visibility = :visible')
            ->andWhere('e.startDate > :now')
            ->setParameter('approved', Event::STATUS_APPROVED)
            ->setParameter('visible', Event::VISIBILITY_VISIBLE)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par organisateur
     */
    public function findByOrganizer(string $organizerType, int $organizerId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organizerType = :type')
            ->andWhere('e.organizerId = :id')
            ->setParameter('type', $organizerType)
            ->setParameter('id', $organizerId)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements créés par un utilisateur
     */
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par jeu
     */
    public function findByGame(Game $game, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.games', 'g')
            ->where('g = :game')
            ->setParameter('game', $game);

        if ($onlyVisible) {
            $qb->andWhere('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les événements par type
     */
    public function findByType(string $eventType, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.eventType = :type')
            ->setParameter('type', $eventType);

        if ($onlyVisible) {
            $qb->andWhere('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les événements avec filtres combinés
     */
    public function findWithFilters(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('e');

        // Filtre par visibilité (défaut: visible uniquement)
        if (!isset($filters['include_hidden']) || !$filters['include_hidden']) {
            $qb->where('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        // Filtre par type d'événement
        if (isset($filters['event_type'])) {
            $qb->andWhere('e.eventType = :eventType')
               ->setParameter('eventType', $filters['event_type']);
        }

        // Filtre par jeu
        if (isset($filters['game_id'])) {
            $qb->join('e.games', 'g')
               ->andWhere('g.id = :gameId')
               ->setParameter('gameId', $filters['game_id']);
        }

        // Filtre par dates
        if (isset($filters['start_date'])) {
            $qb->andWhere('e.startDate >= :startDate')
               ->setParameter('startDate', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $qb->andWhere('e.startDate <= :endDate')
               ->setParameter('endDate', $filters['end_date']);
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filtre par organisateur
        if (isset($filters['organizer_type']) && isset($filters['organizer_id'])) {
            $qb->andWhere('e.organizerType = :organizerType')
               ->andWhere('e.organizerId = :organizerId')
               ->setParameter('organizerType', $filters['organizer_type'])
               ->setParameter('organizerId', $filters['organizer_id']);
        }

        // Filtre par tag
        if (isset($filters['tag'])) {
            $qb->andWhere('JSON_CONTAINS(e.tags, :tag) = 1')
               ->setParameter('tag', json_encode($filters['tag']));
        }

        // Filtre par lieu (en ligne ou physique)
        if (isset($filters['is_online'])) {
            $qb->andWhere('e.isOnline = :isOnline')
               ->setParameter('isOnline', $filters['is_online']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'startDate';
        $orderDirection = $filters['order_direction'] ?? 'ASC';

        // Mapper les noms de champs frontend vers backend
        $fieldMapping = [
            'start_date' => 'startDate',
            'created_at' => 'createdAt',
            'current_participants' => 'currentParticipants',
            'title' => 'title'
        ];

        $actualField = $fieldMapping[$orderBy] ?? $orderBy;
        $qb->orderBy('e.' . $actualField, $orderDirection);

        // Pagination
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les événements avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        // Applique les mêmes filtres que findWithFilters (sans pagination)
        if (!isset($filters['include_hidden']) || !$filters['include_hidden']) {
            $qb->where('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        if (isset($filters['event_type'])) {
            $qb->andWhere('e.eventType = :eventType')
               ->setParameter('eventType', $filters['event_type']);
        }

        if (isset($filters['game_id'])) {
            $qb->join('e.games', 'g')
               ->andWhere('g.id = :gameId')
               ->setParameter('gameId', $filters['game_id']);
        }

        if (isset($filters['start_date'])) {
            $qb->andWhere('e.startDate >= :startDate')
               ->setParameter('startDate', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $qb->andWhere('e.startDate <= :endDate')
               ->setParameter('endDate', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['organizer_type']) && isset($filters['organizer_id'])) {
            $qb->andWhere('e.organizerType = :organizerType')
               ->andWhere('e.organizerId = :organizerId')
               ->setParameter('organizerType', $filters['organizer_type'])
               ->setParameter('organizerId', $filters['organizer_id']);
        }

        if (isset($filters['tag'])) {
            $qb->andWhere('JSON_CONTAINS(e.tags, :tag) = 1')
               ->setParameter('tag', json_encode($filters['tag']));
        }

        if (isset($filters['is_online'])) {
            $qb->andWhere('e.isOnline = :isOnline')
               ->setParameter('isOnline', $filters['is_online']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les événements populaires (avec le plus d'inscriptions)
     */
    public function findPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :approved')
            ->andWhere('e.visibility = :visible')
            ->setParameter('approved', Event::STATUS_APPROVED)
            ->setParameter('visible', Event::VISIBILITY_VISIBLE)
            ->orderBy('e.currentParticipants', 'DESC')
            ->addOrderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements récemment créés
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :approved')
            ->andWhere('e.visibility = :visible')
            ->setParameter('approved', Event::STATUS_APPROVED)
            ->setParameter('visible', Event::VISIBILITY_VISIBLE)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements qui se terminent bientôt (pour les inscriptions)
     */
    public function findEndingSoon(int $hours = 24): array
    {
        $deadline = new \DateTimeImmutable("+{$hours} hours");
        
        return $this->createQueryBuilder('e')
            ->where('e.status = :approved')
            ->andWhere('e.visibility = :visible')
            ->andWhere('e.registrationDeadline <= :deadline')
            ->andWhere('e.registrationDeadline > :now')
            ->setParameter('approved', Event::STATUS_APPROVED)
            ->setParameter('visible', Event::VISIBILITY_VISIBLE)
            ->setParameter('deadline', $deadline)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.registrationDeadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche textuelle dans titre et description
     */
    public function searchByText(string $query, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.title LIKE :query OR e.description LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($onlyVisible) {
            $qb->andWhere('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Statistiques générales des événements
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('e');
        
        $total = $qb->select('COUNT(e.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

        $approved = $qb->select('COUNT(e.id)')
                       ->where('e.status = :approved')
                       ->setParameter('approved', Event::STATUS_APPROVED)
                       ->getQuery()
                       ->getSingleScalarResult();

        $pending = $qb->select('COUNT(e.id)')
                      ->where('e.status = :pending')
                      ->setParameter('pending', Event::STATUS_PENDING_REVIEW)
                      ->getQuery()
                      ->getSingleScalarResult();

        $upcoming = $qb->select('COUNT(e.id)')
                       ->where('e.status = :approved')
                       ->andWhere('e.startDate > :now')
                       ->setParameter('approved', Event::STATUS_APPROVED)
                       ->setParameter('now', new \DateTimeImmutable())
                       ->getQuery()
                       ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'approved' => (int) $approved,
            'pending_review' => (int) $pending,
            'upcoming' => (int) $upcoming,
        ];
    }

    /**
     * Trouve les événements par tags
     */
    public function findByTags(array $tags, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($onlyVisible) {
            $qb->where('e.status = :approved')
               ->andWhere('e.visibility = :visible')
               ->setParameter('approved', Event::STATUS_APPROVED)
               ->setParameter('visible', Event::VISIBILITY_VISIBLE);
        }

        foreach ($tags as $i => $tag) {
            $qb->andWhere("JSON_CONTAINS(e.tags, :tag{$i}) = 1")
               ->setParameter("tag{$i}", json_encode($tag));
        }

        return $qb->orderBy('e.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}