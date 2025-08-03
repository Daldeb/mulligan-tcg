<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\User;
use App\Entity\GameFormat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    /**
     * Trouve les tournois visibles pour les utilisateurs publics
     */
    public function findVisibleTournaments(int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE)
            ->orderBy('t.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois par format de jeu
     */
    public function findByGameFormat(GameFormat $gameFormat, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.gameFormat = :gameFormat')
            ->setParameter('gameFormat', $gameFormat);

        if ($onlyVisible) {
            $qb->andWhere('t.status = :approved')
               ->andWhere('t.visibility = :visible')
               ->setParameter('approved', Tournament::STATUS_APPROVED)
               ->setParameter('visible', Tournament::VISIBILITY_VISIBLE);
        }

        return $qb->orderBy('t.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les tournois par format de tournoi (Swiss, Elimination, etc.)
     */
    public function findByTournamentFormat(string $tournamentFormat, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.tournamentFormat = :format')
            ->setParameter('format', $tournamentFormat);

        if ($onlyVisible) {
            $qb->andWhere('t.status = :approved')
               ->andWhere('t.visibility = :visible')
               ->setParameter('approved', Tournament::STATUS_APPROVED)
               ->setParameter('visible', Tournament::VISIBILITY_VISIBLE);
        }

        return $qb->orderBy('t.startDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Trouve les tournois en cours
     */
    public function findInProgress(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :inProgress')
            ->setParameter('inProgress', Tournament::STATUS_IN_PROGRESS)
            ->orderBy('t.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois par phase
     */
    public function findByPhase(string $phase): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.currentPhase = :phase')
            ->setParameter('phase', $phase)
            ->orderBy('t.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois en phase d'inscription
     */
    public function findOpenForRegistration(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.currentPhase = :registration')
            ->andWhere('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->andWhere('t.registrationDeadline IS NULL OR t.registrationDeadline > :now')
            ->andWhere('t.maxParticipants IS NULL OR t.currentParticipants < t.maxParticipants')
            ->setParameter('registration', Tournament::PHASE_REGISTRATION)
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois récemment terminés
     */
    public function findRecentlyFinished(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.currentPhase = :finished')
            ->setParameter('finished', Tournament::PHASE_FINISHED)
            ->orderBy('t.finishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois avec filtres spécifiques aux tournois
     */
    public function findWithTournamentFilters(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('t');

        // Filtre par visibilité (défaut: visible uniquement)
        if (!isset($filters['include_hidden']) || !$filters['include_hidden']) {
            $qb->where('t.status = :approved')
               ->andWhere('t.visibility = :visible')
               ->setParameter('approved', Tournament::STATUS_APPROVED)
               ->setParameter('visible', Tournament::VISIBILITY_VISIBLE);
        }

        // Filtre par format de jeu
        if (isset($filters['game_format_id'])) {
            $qb->andWhere('t.gameFormat = :gameFormatId')
               ->setParameter('gameFormatId', $filters['game_format_id']);
        }

        // Filtre par format de tournoi
        if (isset($filters['tournament_format'])) {
            $qb->andWhere('t.tournamentFormat = :tournamentFormat')
               ->setParameter('tournamentFormat', $filters['tournament_format']);
        }

        // Filtre par phase
        if (isset($filters['current_phase'])) {
            $qb->andWhere('t.currentPhase = :currentPhase')
               ->setParameter('currentPhase', $filters['current_phase']);
        }

        // Filtre par prize pool
        if (isset($filters['min_prize_pool'])) {
            $qb->andWhere('t.prizePool >= :minPrizePool')
               ->setParameter('minPrizePool', $filters['min_prize_pool']);
        }

        if (isset($filters['max_prize_pool'])) {
            $qb->andWhere('t.prizePool <= :maxPrizePool')
               ->setParameter('maxPrizePool', $filters['max_prize_pool']);
        }

        // Filtre par nombre de participants
        if (isset($filters['min_participants'])) {
            $qb->andWhere('t.currentParticipants >= :minParticipants')
               ->setParameter('minParticipants', $filters['min_participants']);
        }

        if (isset($filters['max_participants'])) {
            $qb->andWhere('t.maxParticipants <= :maxParticipants')
               ->setParameter('maxParticipants', $filters['max_participants']);
        }

        // Filtre par requirement de decklist
        if (isset($filters['require_decklist'])) {
            $qb->andWhere('t.requireDecklist = :requireDecklist')
               ->setParameter('requireDecklist', $filters['require_decklist']);
        }

        // Filtre par dates
        if (isset($filters['start_date'])) {
            $qb->andWhere('t.startDate >= :startDate')
               ->setParameter('startDate', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $qb->andWhere('t.startDate <= :endDate')
               ->setParameter('endDate', $filters['end_date']);
        }

        // Filtre par organisateur
        if (isset($filters['organizer_type']) && isset($filters['organizer_id'])) {
            $qb->andWhere('t.organizerType = :organizerType')
               ->andWhere('t.organizerId = :organizerId')
               ->setParameter('organizerType', $filters['organizer_type'])
               ->setParameter('organizerId', $filters['organizer_id']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'startDate';
        $orderDirection = $filters['order_direction'] ?? 'ASC';
        
        // Tri spécial pour les tournois
        if ($orderBy === 'participants') {
            $qb->orderBy('t.currentParticipants', $orderDirection);
        } elseif ($orderBy === 'prize_pool') {
            $qb->orderBy('t.prizePool', $orderDirection);
        } else {
            $qb->orderBy('t.' . $orderBy, $orderDirection);
        }

        // Pagination
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les tournois avec filtres
     */
    public function countWithTournamentFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        // Applique les mêmes filtres que findWithTournamentFilters
        if (!isset($filters['include_hidden']) || !$filters['include_hidden']) {
            $qb->where('t.status = :approved')
               ->andWhere('t.visibility = :visible')
               ->setParameter('approved', Tournament::STATUS_APPROVED)
               ->setParameter('visible', Tournament::VISIBILITY_VISIBLE);
        }

        if (isset($filters['game_format_id'])) {
            $qb->andWhere('t.gameFormat = :gameFormatId')
               ->setParameter('gameFormatId', $filters['game_format_id']);
        }

        if (isset($filters['tournament_format'])) {
            $qb->andWhere('t.tournamentFormat = :tournamentFormat')
               ->setParameter('tournamentFormat', $filters['tournament_format']);
        }

        if (isset($filters['current_phase'])) {
            $qb->andWhere('t.currentPhase = :currentPhase')
               ->setParameter('currentPhase', $filters['current_phase']);
        }

        if (isset($filters['min_prize_pool'])) {
            $qb->andWhere('t.prizePool >= :minPrizePool')
               ->setParameter('minPrizePool', $filters['min_prize_pool']);
        }

        if (isset($filters['max_prize_pool'])) {
            $qb->andWhere('t.prizePool <= :maxPrizePool')
               ->setParameter('maxPrizePool', $filters['max_prize_pool']);
        }

        if (isset($filters['min_participants'])) {
            $qb->andWhere('t.currentParticipants >= :minParticipants')
               ->setParameter('minParticipants', $filters['min_participants']);
        }

        if (isset($filters['max_participants'])) {
            $qb->andWhere('t.maxParticipants <= :maxParticipants')
               ->setParameter('maxParticipants', $filters['max_participants']);
        }

        if (isset($filters['require_decklist'])) {
            $qb->andWhere('t.requireDecklist = :requireDecklist')
               ->setParameter('requireDecklist', $filters['require_decklist']);
        }

        if (isset($filters['start_date'])) {
            $qb->andWhere('t.startDate >= :startDate')
               ->setParameter('startDate', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $qb->andWhere('t.startDate <= :endDate')
               ->setParameter('endDate', $filters['end_date']);
        }

        if (isset($filters['organizer_type']) && isset($filters['organizer_id'])) {
            $qb->andWhere('t.organizerType = :organizerType')
               ->andWhere('t.organizerId = :organizerId')
               ->setParameter('organizerType', $filters['organizer_type'])
               ->setParameter('organizerId', $filters['organizer_id']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les tournois les plus populaires (par nombre de participants)
     */
    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE)
            ->orderBy('t.currentParticipants', 'DESC')
            ->addOrderBy('t.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois avec le plus gros prize pool
     */
    public function findHighestPrizePool(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->andWhere('t.prizePool IS NOT NULL')
            ->andWhere('t.prizePool > 0')
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE)
            ->orderBy('t.prizePool', 'DESC')
            ->addOrderBy('t.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques spécifiques aux tournois
     */
    public function getTournamentStats(): array
    {
        $qb = $this->createQueryBuilder('t');
        
        $total = $qb->select('COUNT(t.id)')
                    ->getQuery()
                    ->getSingleScalarResult();

        $inProgress = $qb->select('COUNT(t.id)')
                         ->where('t.status = :inProgress')
                         ->setParameter('inProgress', Tournament::STATUS_IN_PROGRESS)
                         ->getQuery()
                         ->getSingleScalarResult();

        $openRegistration = $qb->select('COUNT(t.id)')
                               ->where('t.currentPhase = :registration')
                               ->andWhere('t.status = :approved')
                               ->setParameter('registration', Tournament::PHASE_REGISTRATION)
                               ->setParameter('approved', Tournament::STATUS_APPROVED)
                               ->getQuery()
                               ->getSingleScalarResult();

        $finished = $qb->select('COUNT(t.id)')
                       ->where('t.currentPhase = :finished')
                       ->setParameter('finished', Tournament::PHASE_FINISHED)
                       ->getQuery()
                       ->getSingleScalarResult();

        // Moyenne de participants
        $avgParticipants = $qb->select('AVG(t.currentParticipants)')
                              ->where('t.currentParticipants > 0')
                              ->getQuery()
                              ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'in_progress' => (int) $inProgress,
            'open_registration' => (int) $openRegistration,
            'finished' => (int) $finished,
            'average_participants' => round((float) $avgParticipants, 1),
        ];
    }

    /**
     * Trouve les tournois où un utilisateur peut s'inscrire
     */
    public function findAvailableForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.registrations', 'r', 'WITH', 'r.user = :user')
            ->where('t.currentPhase = :registration')
            ->andWhere('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->andWhere('r.id IS NULL') // User not already registered
            ->andWhere('t.registrationDeadline IS NULL OR t.registrationDeadline > :now')
            ->andWhere('t.maxParticipants IS NULL OR t.currentParticipants < t.maxParticipants')
            ->setParameter('user', $user)
            ->setParameter('registration', Tournament::PHASE_REGISTRATION)
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois où un utilisateur est inscrit
     */
    public function findByParticipant(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.registrations', 'r')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['REGISTERED', 'CONFIRMED'])
            ->orderBy('t.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tournois nécessitant une action (rounds à générer, etc.)
     */
    public function findRequiringAction(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :inProgress')
            ->andWhere('t.currentPhase IN (:actionPhases)')
            ->setParameter('inProgress', Tournament::STATUS_IN_PROGRESS)
            ->setParameter('actionPhases', [Tournament::PHASE_SWISS, Tournament::PHASE_TOP_CUT])
            ->orderBy('t.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les tournois par multiple critères avec optimisations
     */
    public function searchTournaments(string $query = '', array $gameFormatIds = [], string $phase = '', int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :approved')
            ->andWhere('t.visibility = :visible')
            ->setParameter('approved', Tournament::STATUS_APPROVED)
            ->setParameter('visible', Tournament::VISIBILITY_VISIBLE);

        // Recherche textuelle
        if (!empty($query)) {
            $qb->andWhere('t.title LIKE :query OR t.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Filtrer par formats de jeu
        if (!empty($gameFormatIds)) {
            $qb->andWhere('t.gameFormat IN (:gameFormatIds)')
               ->setParameter('gameFormatIds', $gameFormatIds);
        }

        // Filtrer par phase
        if (!empty($phase)) {
            $qb->andWhere('t.currentPhase = :phase')
               ->setParameter('phase', $phase);
        }

        return $qb->orderBy('t.startDate', 'ASC')
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }
}