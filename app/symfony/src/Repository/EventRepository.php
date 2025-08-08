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

    /**
 * Trouve les événements dans une plage de temps pour les notifications
 */
public function findEventsInTimeRange(
    \DateTimeImmutable $from, 
    \DateTimeImmutable $to, 
    array $statuses = ['APPROVED']
): array {
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.startDate >= :from')
        ->andWhere('e.startDate <= :to')
        ->andWhere('e.status IN (:statuses)')
        ->andWhere('e.visibility = :visibility')
        ->setParameter('from', $from)
        ->setParameter('to', $to)
        ->setParameter('statuses', $statuses)
        ->setParameter('visibility', Event::VISIBILITY_VISIBLE)
        ->orderBy('e.startDate', 'ASC');

    return $qb->getQuery()->getResult();
}

/**
 * Trouve les événements qui se terminent dans une plage de temps
 */
public function findEventsEndingInTimeRange(
    \DateTimeImmutable $from, 
    \DateTimeImmutable $to, 
    array $statuses = ['IN_PROGRESS']
): array {
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.endDate IS NOT NULL')
        ->andWhere('e.endDate >= :from')
        ->andWhere('e.endDate <= :to')
        ->andWhere('e.status IN (:statuses)')
        ->andWhere('e.visibility = :visibility')
        ->setParameter('from', $from)
        ->setParameter('to', $to)
        ->setParameter('statuses', $statuses)
        ->setParameter('visibility', Event::VISIBILITY_VISIBLE)
        ->orderBy('e.endDate', 'ASC');

    return $qb->getQuery()->getResult();
}

/**
 * Trouve les événements terminés dans une plage de temps
 */
public function findEventsEndedInTimeRange(
    \DateTimeImmutable $from, 
    \DateTimeImmutable $to, 
    array $statuses = ['IN_PROGRESS', 'FINISHED']
): array {
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.status IN (:statuses)')
        ->andWhere('e.visibility = :visibility')
        ->setParameter('statuses', $statuses)
        ->setParameter('visibility', Event::VISIBILITY_VISIBLE)
        ->orderBy('e.startDate', 'ASC');

    // Si endDate est définie, l'utiliser, sinon utiliser startDate comme approximation
    $qb->andWhere(
        $qb->expr()->orX(
            $qb->expr()->andX(
                'e.endDate IS NOT NULL',
                'e.endDate >= :from',
                'e.endDate <= :to'
            ),
            $qb->expr()->andX(
                'e.endDate IS NULL',
                'e.startDate >= :from_start',
                'e.startDate <= :to_start'
            )
        )
    );

    $qb->setParameter('from', $from)
       ->setParameter('to', $to)
       ->setParameter('from_start', $from->modify('-6 hours')) // Approximation pour événements sans endDate
       ->setParameter('to_start', $to->modify('-6 hours'));

    return $qb->getQuery()->getResult();
}

/**
 * Compte les événements par statut
 */
public function countByStatus(string $status): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.status = :status')
        ->setParameter('status', $status)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les événements par visibilité
 */
public function countByVisibility(string $visibility): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.visibility = :visibility')
        ->setParameter('visibility', $visibility)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Trouve les événements en attente de validation
 */
public function findPendingReview(int $limit = null): array
{
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.status = :status')
        ->setParameter('status', Event::STATUS_PENDING_REVIEW)
        ->orderBy('e.createdAt', 'ASC');

    if ($limit) {
        $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Trouve les événements en attente depuis X jours
 */
public function findOldPendingReview(int $daysSince): array
{
    $since = new \DateTimeImmutable("-{$daysSince} days");
    
    return $this->createQueryBuilder('e')
        ->andWhere('e.status = :status')
        ->andWhere('e.createdAt <= :since')
        ->setParameter('status', Event::STATUS_PENDING_REVIEW)
        ->setParameter('since', $since)
        ->orderBy('e.createdAt', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Trouve les événements à venir non validés (danger)
 */
public function findUpcomingUnvalidated(int $hoursUntilStart): array
{
    $deadline = new \DateTimeImmutable("+{$hoursUntilStart} hours");
    
    return $this->createQueryBuilder('e')
        ->andWhere('e.status IN (:statuses)')
        ->andWhere('e.startDate <= :deadline')
        ->andWhere('e.startDate >= :now')
        ->setParameter('statuses', [Event::STATUS_PENDING_REVIEW, Event::STATUS_DRAFT])
        ->setParameter('deadline', $deadline)
        ->setParameter('now', new \DateTimeImmutable())
        ->orderBy('e.startDate', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Compte les événements créés depuis une date
 */
public function countCreatedSince(\DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.createdAt >= :since')
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les événements approuvés depuis une date
 */
public function countApprovedSince(\DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.status = :status')
        ->andWhere('e.reviewedAt >= :since')
        ->setParameter('status', Event::STATUS_APPROVED)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les événements rejetés depuis une date
 */
public function countRejectedSince(\DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.status = :status')
        ->andWhere('e.reviewedAt >= :since')
        ->setParameter('status', Event::STATUS_REJECTED)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Statistiques par type d'événement
 */
public function countByTypeAndPeriod(string $eventType, \DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.eventType = :eventType')
        ->andWhere('e.createdAt >= :since')
        ->setParameter('eventType', $eventType)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Statistiques par type d'organisateur
 */
public function countByOrganizerTypeAndPeriod(string $organizerType, \DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.organizerType = :organizerType')
        ->andWhere('e.createdAt >= :since')
        ->setParameter('organizerType', $organizerType)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Trouve les événements avec des actions admin récentes
 */
public function findWithAdminActions(int $limit = 50, int $offset = 0): array
{
    return $this->createQueryBuilder('e')
        ->andWhere('e.reviewedBy IS NOT NULL')
        ->andWhere('e.reviewedAt IS NOT NULL')
        ->orderBy('e.reviewedAt', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult($offset)
        ->getQuery()
        ->getResult();
}

/**
 * Compte les événements avec des actions admin
 */
public function countWithAdminActions(): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.reviewedBy IS NOT NULL')
        ->andWhere('e.reviewedAt IS NOT NULL')
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte tous les événements
 */
public function countAll(): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les événements par créateur
 */
public function countByCreator(User $creator): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.createdBy = :creator')
        ->setParameter('creator', $creator)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les événements actifs par créateur
 */
public function countActiveByCreator(User $creator): int
{
    return $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->andWhere('e.createdBy = :creator')
        ->andWhere('e.status IN (:activeStatuses)')
        ->setParameter('creator', $creator)
        ->setParameter('activeStatuses', [
            Event::STATUS_APPROVED, 
            Event::STATUS_IN_PROGRESS, 
            Event::STATUS_PENDING_REVIEW
        ])
        ->getQuery()
        ->getSingleScalarResult();
}
}