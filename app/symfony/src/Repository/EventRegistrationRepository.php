<?php

namespace App\Repository;

use App\Entity\EventRegistration;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    /**
     * Trouve les inscriptions d'un événement
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions actives d'un événement
     */
    public function findActiveByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('event', $event)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions actives d'un utilisateur
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve l'inscription active d'un utilisateur à un événement (pas annulée)
     */
    public function findActiveUserRegistration(User $user, Event $event): ?EventRegistration
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.event = :event')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve l'inscription annulée d'un utilisateur à un événement
     */
    public function findCancelledUserRegistration(User $user, Event $event): ?EventRegistration
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.event = :event')
            ->andWhere('r.status = :cancelled')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->setParameter('cancelled', EventRegistration::STATUS_CANCELLED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
 * Trouve l'inscription d'un utilisateur à un événement (tous statuts confondus)
 */
public function findUserRegistration(User $user, Event $event): ?EventRegistration
{
    return $this->findOneBy([
        'user' => $user,
        'event' => $event
    ]);
}

    /**
     * Vérifie si un utilisateur est inscrit à un événement (inscription active)
     */
    public function isUserRegistered(User $user, Event $event): bool
    {
        $registration = $this->findActiveUserRegistration($user, $event);
        return $registration !== null;
    }

    /**
     * Trouve les participants confirmés d'un tournoi (pour les pairings)
     */
    public function findConfirmedParticipants(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :tournament')
            ->andWhere('r.status = :confirmed')
            ->andWhere('r.checkedIn = :checkedIn')
            ->setParameter('tournament', $tournament)
            ->setParameter('confirmed', EventRegistration::STATUS_CONFIRMED)
            ->setParameter('checkedIn', true)
            ->orderBy('r.seedNumber', 'ASC')
            ->addOrderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions nécessitant un check-in
     */
    public function findPendingCheckIn(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.status IN (:activeStatuses)')
            ->andWhere('r.checkedIn = :notCheckedIn')
            ->setParameter('event', $event)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->setParameter('notCheckedIn', false)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les participants ayant soumis une decklist
     */
    public function findWithDeckList(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :tournament')
            ->andWhere('r.deckListSubmitted = :submitted')
            ->setParameter('tournament', $tournament)
            ->setParameter('submitted', true)
            ->orderBy('r.deckListSubmittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les participants n'ayant pas soumis de decklist
     */
    public function findMissingDeckList(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :tournament')
            ->andWhere('r.status IN (:activeStatuses)')
            ->andWhere('r.deckListSubmitted = :notSubmitted')
            ->setParameter('tournament', $tournament)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->setParameter('notSubmitted', false)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions par statut
     */
    public function findByStatus(Event $event, string $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les classements d'un tournoi
     */
    public function findTournamentStandings(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :tournament')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('tournament', $tournament)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->orderBy('JSON_EXTRACT(r.tournamentStats, "$.match_points")', 'DESC')
            ->addOrderBy('JSON_EXTRACT(r.tournamentStats, "$.opponent_match_win_percentage")', 'DESC')
            ->addOrderBy('JSON_EXTRACT(r.tournamentStats, "$.game_win_percentage")', 'DESC')
            ->addOrderBy('JSON_EXTRACT(r.tournamentStats, "$.opponent_game_win_percentage")', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les finalistes d'un tournoi (classement final)
     */
    public function findTournamentFinalRanking(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :tournament')
            ->andWhere('r.finalRanking IS NOT NULL')
            ->setParameter('tournament', $tournament)
            ->orderBy('r.finalRanking', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les inscriptions par statut pour un événement
     */
    public function countByStatus(Event $event): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Trouve les inscriptions récentes
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->orderBy('r.registeredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les inscriptions nécessitant une action (validation, etc.)
     */
    public function findRequiringAction(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.status = :registered')
            ->setParameter('event', $event)
            ->setParameter('registered', EventRegistration::STATUS_REGISTERED)
            ->orderBy('r.registeredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des inscriptions
     */
    public function getRegistrationStats(Event $event): array
    {
        $qb = $this->createQueryBuilder('r');
        
        $total = $qb->select('COUNT(r.id)')
                    ->where('r.event = :event')
                    ->setParameter('event', $event)
                    ->getQuery()
                    ->getSingleScalarResult();

        $active = $qb->select('COUNT(r.id)')
                     ->where('r.event = :event')
                     ->andWhere('r.status IN (:activeStatuses)')
                     ->setParameter('event', $event)
                     ->setParameter('activeStatuses', [
                         EventRegistration::STATUS_REGISTERED,
                         EventRegistration::STATUS_CONFIRMED
                     ])
                     ->getQuery()
                     ->getSingleScalarResult();

        $checkedIn = $qb->select('COUNT(r.id)')
                        ->where('r.event = :event')
                        ->andWhere('r.checkedIn = :checkedIn')
                        ->setParameter('event', $event)
                        ->setParameter('checkedIn', true)
                        ->getQuery()
                        ->getSingleScalarResult();

        $stats = [
            'total' => (int) $total,
            'active' => (int) $active,
            'checked_in' => (int) $checkedIn,
            'cancelled' => 0,
            'no_show' => 0,
            'disqualified' => 0
        ];

        // Compter par statut
        $statusCounts = $this->countByStatus($event);
        $stats['cancelled'] = $statusCounts[EventRegistration::STATUS_CANCELLED] ?? 0;
        $stats['no_show'] = $statusCounts[EventRegistration::STATUS_NO_SHOW] ?? 0;
        $stats['disqualified'] = $statusCounts[EventRegistration::STATUS_DISQUALIFIED] ?? 0;

        return $stats;
    }

    // Dans EventRegistrationRepository.php, modifier la méthode findWithFilters :

    public function findWithFilters(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->join('r.user', 'u');

        // Filtre par événement
        if (isset($filters['event_id'])) {
            $qb->andWhere('e.id = :eventId')
            ->setParameter('eventId', $filters['event_id']);
        }

        // Filtre par utilisateur
        if (isset($filters['user_id'])) {
            $qb->andWhere('u.id = :userId')
            ->setParameter('userId', $filters['user_id']);
        }

        // Filtre par statut unique
        if (isset($filters['status'])) {
            $qb->andWhere('r.status = :status')
            ->setParameter('status', $filters['status']);
        }

        // NOUVEAU : Filtre par statuts multiples
        if (isset($filters['status_in']) && is_array($filters['status_in'])) {
            $qb->andWhere('r.status IN (:statusIn)')
            ->setParameter('statusIn', $filters['status_in']);
        }

        // Filtre par check-in
        if (isset($filters['checked_in'])) {
            $qb->andWhere('r.checkedIn = :checkedIn')
            ->setParameter('checkedIn', $filters['checked_in']);
        }

        // Filtre par soumission de decklist
        if (isset($filters['deck_list_submitted'])) {
            $qb->andWhere('r.deckListSubmitted = :deckListSubmitted')
            ->setParameter('deckListSubmitted', $filters['deck_list_submitted']);
        }

        // Filtre par dates
        if (isset($filters['registered_after'])) {
            $qb->andWhere('r.registeredAt >= :registeredAfter')
            ->setParameter('registeredAfter', $filters['registered_after']);
        }

        if (isset($filters['registered_before'])) {
            $qb->andWhere('r.registeredAt <= :registeredBefore')
            ->setParameter('registeredBefore', $filters['registered_before']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'registeredAt';
        $orderDirection = $filters['order_direction'] ?? 'ASC';
        
        if ($orderBy === 'user_pseudo') {
            $qb->orderBy('u.pseudo', $orderDirection);
        } elseif ($orderBy === 'event_start') {
            $qb->orderBy('e.startDate', $orderDirection);
        } else {
            $qb->orderBy('r.' . $orderBy, $orderDirection);
        }

        // Pagination
        $qb->setFirstResult($offset)
        ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les prochains événements d'un utilisateur
     */
    public function findUpcomingForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:activeStatuses)')
            ->andWhere('e.startDate > :now')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', [
                EventRegistration::STATUS_REGISTERED,
                EventRegistration::STATUS_CONFIRMED
            ])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Assigne automatiquement les seed numbers pour un tournoi
     */
    public function assignSeedNumbers(Tournament $tournament): void
    {
        $participants = $this->findConfirmedParticipants($tournament);
        
        foreach ($participants as $index => $registration) {
            $registration->setSeedNumber($index + 1);
        }
        
        $this->getEntityManager()->flush();
    }
}