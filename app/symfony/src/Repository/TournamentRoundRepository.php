<?php

namespace App\Repository;

use App\Entity\TournamentRound;
use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentRound>
 */
class TournamentRoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentRound::class);
    }

    /**
     * Trouve les rounds d'un tournoi
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('r.roundNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds Swiss d'un tournoi
     */
    public function findSwissRounds(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.roundType = :type')
            ->setParameter('tournament', $tournament)
            ->setParameter('type', TournamentRound::TYPE_SWISS)
            ->orderBy('r.roundNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds de Top Cut d'un tournoi
     */
    public function findTopCutRounds(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.roundType = :type')
            ->setParameter('tournament', $tournament)
            ->setParameter('type', TournamentRound::TYPE_TOP_CUT)
            ->orderBy('r.roundNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le round actuel d'un tournoi
     */
    public function findCurrentRound(Tournament $tournament): ?TournamentRound
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('tournament', $tournament)
            ->setParameter('activeStatuses', [
                TournamentRound::STATUS_PENDING,
                TournamentRound::STATUS_ACTIVE
            ])
            ->orderBy('r.roundNumber', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le dernier round terminé d'un tournoi
     */
    public function findLastFinishedRound(Tournament $tournament): ?TournamentRound
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.status = :finished')
            ->setParameter('tournament', $tournament)
            ->setParameter('finished', TournamentRound::STATUS_FINISHED)
            ->orderBy('r.roundNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un round spécifique par numéro
     */
    public function findByTournamentAndNumber(Tournament $tournament, int $roundNumber): ?TournamentRound
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.roundNumber = :roundNumber')
            ->setParameter('tournament', $tournament)
            ->setParameter('roundNumber', $roundNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les rounds actifs (en cours)
     */
    public function findActiveRounds(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :active')
            ->setParameter('active', TournamentRound::STATUS_ACTIVE)
            ->orderBy('r.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds en attente de démarrage
     */
    public function findPendingRounds(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :pending')
            ->andWhere('r.pairingsGenerated = :generated')
            ->setParameter('pending', TournamentRound::STATUS_PENDING)
            ->setParameter('generated', true)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds dépassant la limite de temps
     */
    public function findOvertime(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :active')
            ->andWhere('r.timeLimit IS NOT NULL')
            ->andWhere('r.startedAt IS NOT NULL')
            ->andWhere('TIMESTAMPDIFF(MINUTE, r.startedAt, CURRENT_TIMESTAMP()) > r.timeLimit')
            ->setParameter('active', TournamentRound::STATUS_ACTIVE)
            ->orderBy('r.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds nécessitant une génération de pairings
     */
    public function findNeedingPairings(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :pending')
            ->andWhere('r.pairingsGenerated = :notGenerated')
            ->setParameter('pending', TournamentRound::STATUS_PENDING)
            ->setParameter('notGenerated', false)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le prochain numéro de round pour un tournoi
     */
    public function getNextRoundNumber(Tournament $tournament, string $roundType): int
    {
        $maxRound = $this->createQueryBuilder('r')
            ->select('MAX(r.roundNumber)')
            ->where('r.tournament = :tournament')
            ->andWhere('r.roundType = :type')
            ->setParameter('tournament', $tournament)
            ->setParameter('type', $roundType)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxRound ?? 0) + 1;
    }

    /**
     * Compte les rounds par statut pour un tournoi
     */
    public function countByStatusInTournament(Tournament $tournament): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.tournament = :tournament')
            ->setParameter('tournament', $tournament)
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
     * Statistiques des rounds pour un tournoi
     */
    public function getRoundStats(Tournament $tournament): array
    {
        $qb = $this->createQueryBuilder('r');
        
        $total = $qb->select('COUNT(r.id)')
                    ->where('r.tournament = :tournament')
                    ->setParameter('tournament', $tournament)
                    ->getQuery()
                    ->getSingleScalarResult();

        $finished = $qb->select('COUNT(r.id)')
                       ->where('r.tournament = :tournament')
                       ->andWhere('r.status = :finished')
                       ->setParameter('tournament', $tournament)
                       ->setParameter('finished', TournamentRound::STATUS_FINISHED)
                       ->getQuery()
                       ->getSingleScalarResult();

        $active = $qb->select('COUNT(r.id)')
                     ->where('r.tournament = :tournament')
                     ->andWhere('r.status = :active')
                     ->setParameter('tournament', $tournament)
                     ->setParameter('active', TournamentRound::STATUS_ACTIVE)
                     ->getQuery()
                     ->getSingleScalarResult();

        $pending = $qb->select('COUNT(r.id)')
                      ->where('r.tournament = :tournament')
                      ->andWhere('r.status = :pending')
                      ->setParameter('tournament', $tournament)
                      ->setParameter('pending', TournamentRound::STATUS_PENDING)
                      ->getQuery()
                      ->getSingleScalarResult();

        // Durée moyenne des rounds terminés
        $avgDuration = $qb->select('AVG(TIMESTAMPDIFF(MINUTE, r.startedAt, r.finishedAt))')
                          ->where('r.tournament = :tournament')
                          ->andWhere('r.status = :finished')
                          ->andWhere('r.startedAt IS NOT NULL')
                          ->andWhere('r.finishedAt IS NOT NULL')
                          ->setParameter('tournament', $tournament)
                          ->setParameter('finished', TournamentRound::STATUS_FINISHED)
                          ->getQuery()
                          ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'finished' => (int) $finished,
            'active' => (int) $active,
            'pending' => (int) $pending,
            'average_duration' => $avgDuration ? round((float) $avgDuration, 1) : null,
        ];
    }

    /**
     * Trouve les rounds avec filtres
     */
    public function findWithFilters(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.tournament', 't');

        // Filtre par tournoi
        if (isset($filters['tournament_id'])) {
            $qb->andWhere('t.id = :tournamentId')
               ->setParameter('tournamentId', $filters['tournament_id']);
        }

        // Filtre par type de round
        if (isset($filters['round_type'])) {
            $qb->andWhere('r.roundType = :roundType')
               ->setParameter('roundType', $filters['round_type']);
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filtre par numéro de round
        if (isset($filters['round_number'])) {
            $qb->andWhere('r.roundNumber = :roundNumber')
               ->setParameter('roundNumber', $filters['round_number']);
        }

        // Filtre par pairings générés
        if (isset($filters['pairings_generated'])) {
            $qb->andWhere('r.pairingsGenerated = :pairingsGenerated')
               ->setParameter('pairingsGenerated', $filters['pairings_generated']);
        }

        // Filtre par limite de temps
        if (isset($filters['has_time_limit'])) {
            if ($filters['has_time_limit']) {
                $qb->andWhere('r.timeLimit IS NOT NULL');
            } else {
                $qb->andWhere('r.timeLimit IS NULL');
            }
        }

        // Filtre par dates
        if (isset($filters['started_after'])) {
            $qb->andWhere('r.startedAt >= :startedAfter')
               ->setParameter('startedAfter', $filters['started_after']);
        }

        if (isset($filters['finished_before'])) {
            $qb->andWhere('r.finishedAt <= :finishedBefore')
               ->setParameter('finishedBefore', $filters['finished_before']);
        }

        // Filtre overtime
        if (isset($filters['overtime']) && $filters['overtime']) {
            $qb->andWhere('r.status = :active')
               ->andWhere('r.timeLimit IS NOT NULL')
               ->andWhere('r.startedAt IS NOT NULL')
               ->andWhere('TIMESTAMPDIFF(MINUTE, r.startedAt, CURRENT_TIMESTAMP()) > r.timeLimit')
               ->setParameter('active', TournamentRound::STATUS_ACTIVE);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'roundNumber';
        $orderDirection = $filters['order_direction'] ?? 'ASC';
        
        if ($orderBy === 'tournament_name') {
            $qb->orderBy('t.title', $orderDirection);
        } else {
            $qb->orderBy('r.' . $orderBy, $orderDirection);
        }

        // Pagination
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les rounds terminés récemment
     */
    public function findRecentlyFinished(int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :finished')
            ->setParameter('finished', TournamentRound::STATUS_FINISHED)
            ->orderBy('r.finishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rounds les plus longs
     */
    public function findLongestRounds(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :finished')
            ->andWhere('r.startedAt IS NOT NULL')
            ->andWhere('r.finishedAt IS NOT NULL')
            ->setParameter('finished', TournamentRound::STATUS_FINISHED)
            ->orderBy('TIMESTAMPDIFF(MINUTE, r.startedAt, r.finishedAt)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un tournoi a des rounds en cours
     */
    public function hasActiveRounds(Tournament $tournament): bool
    {
        $activeRound = $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->andWhere('r.status = :active')
            ->setParameter('tournament', $tournament)
            ->setParameter('active', TournamentRound::STATUS_ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();

        return $activeRound !== null;
    }

    /**
     * Trouve les rounds nécessitant une action urgente
     */
    public function findRequiringUrgentAction(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :active')
            ->andWhere('r.timeLimit IS NOT NULL')
            ->andWhere('r.startedAt IS NOT NULL')
            ->andWhere('TIMESTAMPDIFF(MINUTE, r.startedAt, CURRENT_TIMESTAMP()) > (r.timeLimit + 30)')
            ->setParameter('active', TournamentRound::STATUS_ACTIVE)
            ->orderBy('r.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Met à jour le statut des rounds basé sur leurs matchs
     */
    public function updateRoundStatusFromMatches(Tournament $tournament): void
    {
        $rounds = $this->findByTournament($tournament);
        
        foreach ($rounds as $round) {
            if ($round->isActive()) {
                $round->updateMatchesStatus();
            }
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * Créé le prochain round pour un tournoi
     */
    public function createNextRound(Tournament $tournament, string $roundType): TournamentRound
    {
        $roundNumber = $this->getNextRoundNumber($tournament, $roundType);
        
        $round = new TournamentRound();
        $round->setTournament($tournament);
        $round->setRoundNumber($roundNumber);
        $round->setRoundType($roundType);
        $round->setTimeLimit($tournament->getMatchTimer());
        
        $this->getEntityManager()->persist($round);
        
        return $round;
    }
}