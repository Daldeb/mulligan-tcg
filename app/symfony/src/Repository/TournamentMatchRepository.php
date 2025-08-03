<?php

namespace App\Repository;

use App\Entity\TournamentMatch;
use App\Entity\Tournament;
use App\Entity\TournamentRound;
use App\Entity\EventRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMatch>
 */
class TournamentMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatch::class);
    }

    /**
     * Trouve les matchs d'un tournoi
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('m.round', 'ASC')
            ->addOrderBy('m.tableNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs d'un round
     */
    public function findByRound(TournamentRound $round): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.round = :round')
            ->setParameter('round', $round)
            ->orderBy('m.tableNumber', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs en cours d'un tournoi
     */
    public function findInProgressByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', TournamentMatch::STATUS_IN_PROGRESS)
            ->orderBy('m.tableNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs en attente d'un round
     */
    public function findPendingByRound(TournamentRound $round): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.round = :round')
            ->andWhere('m.status = :status')
            ->setParameter('round', $round)
            ->setParameter('status', TournamentMatch::STATUS_PENDING)
            ->orderBy('m.tableNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs terminés d'un round
     */
    public function findFinishedByRound(TournamentRound $round): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.round = :round')
            ->andWhere('m.status = :status')
            ->setParameter('round', $round)
            ->setParameter('status', TournamentMatch::STATUS_FINISHED)
            ->orderBy('m.finishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs d'un joueur dans un tournoi
     */
    public function findByPlayerInTournament(EventRegistration $player, Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.player1 = :player OR m.player2 = :player')
            ->setParameter('tournament', $tournament)
            ->setParameter('player', $player)
            ->orderBy('m.round', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les byes d'un tournoi
     */
    public function findByesTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :bye OR m.player2 IS NULL')
            ->setParameter('tournament', $tournament)
            ->setParameter('bye', TournamentMatch::STATUS_BYE)
            ->orderBy('m.round', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs par statut dans un tournoi
     */
    public function findByStatusInTournament(Tournament $tournament, string $status): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', $status)
            ->orderBy('m.round', 'ASC')
            ->addOrderBy('m.tableNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les matchs par statut pour un round
     */
    public function countByStatusInRound(TournamentRound $round): array
    {
        $results = $this->createQueryBuilder('m')
            ->select('m.status, COUNT(m.id) as count')
            ->where('m.round = :round')
            ->setParameter('round', $round)
            ->groupBy('m.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Trouve les matchs avec le plus long temps de jeu
     */
    public function findLongestMatches(Tournament $tournament, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :finished')
            ->andWhere('m.duration IS NOT NULL')
            ->setParameter('tournament', $tournament)
            ->setParameter('finished', TournamentMatch::STATUS_FINISHED)
            ->orderBy('m.duration', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les matchs les plus rapides
     */
    public function findFastestMatches(Tournament $tournament, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :finished')
            ->andWhere('m.duration IS NOT NULL')
            ->andWhere('m.duration > 0')
            ->setParameter('tournament', $tournament)
            ->setParameter('finished', TournamentMatch::STATUS_FINISHED)
            ->orderBy('m.duration', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le prochain numéro de table disponible pour un round
     */
    public function getNextTableNumber(TournamentRound $round): int
    {
        $maxTable = $this->createQueryBuilder('m')
            ->select('MAX(m.tableNumber)')
            ->where('m.round = :round')
            ->setParameter('round', $round)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxTable ?? 0) + 1;
    }

    /**
     * Trouve les matchs nécessitant une action (en cours depuis longtemps)
     */
    public function findRequiringAction(Tournament $tournament, int $minutesThreshold = 60): array
    {
        $threshold = new \DateTimeImmutable("-{$minutesThreshold} minutes");

        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :inProgress')
            ->andWhere('m.startedAt < :threshold')
            ->setParameter('tournament', $tournament)
            ->setParameter('inProgress', TournamentMatch::STATUS_IN_PROGRESS)
            ->setParameter('threshold', $threshold)
            ->orderBy('m.startedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des matchs pour un tournoi
     */
    public function getMatchStats(Tournament $tournament): array
    {
        $qb = $this->createQueryBuilder('m');
        
        $total = $qb->select('COUNT(m.id)')
                    ->where('m.tournament = :tournament')
                    ->setParameter('tournament', $tournament)
                    ->getQuery()
                    ->getSingleScalarResult();

        $finished = $qb->select('COUNT(m.id)')
                       ->where('m.tournament = :tournament')
                       ->andWhere('m.status = :finished')
                       ->setParameter('tournament', $tournament)
                       ->setParameter('finished', TournamentMatch::STATUS_FINISHED)
                       ->getQuery()
                       ->getSingleScalarResult();

        $inProgress = $qb->select('COUNT(m.id)')
                         ->where('m.tournament = :tournament')
                         ->andWhere('m.status = :inProgress')
                         ->setParameter('tournament', $tournament)
                         ->setParameter('inProgress', TournamentMatch::STATUS_IN_PROGRESS)
                         ->getQuery()
                         ->getSingleScalarResult();

        $pending = $qb->select('COUNT(m.id)')
                      ->where('m.tournament = :tournament')
                      ->andWhere('m.status = :pending')
                      ->setParameter('tournament', $tournament)
                      ->setParameter('pending', TournamentMatch::STATUS_PENDING)
                      ->getQuery()
                      ->getSingleScalarResult();

        $byes = $qb->select('COUNT(m.id)')
                   ->where('m.tournament = :tournament')
                   ->andWhere('m.status = :bye')
                   ->setParameter('tournament', $tournament)
                   ->setParameter('bye', TournamentMatch::STATUS_BYE)
                   ->getQuery()
                   ->getSingleScalarResult();

        // Durée moyenne des matchs terminés
        $avgDuration = $qb->select('AVG(m.duration)')
                          ->where('m.tournament = :tournament')
                          ->andWhere('m.status = :finished')
                          ->andWhere('m.duration IS NOT NULL')
                          ->setParameter('tournament', $tournament)
                          ->setParameter('finished', TournamentMatch::STATUS_FINISHED)
                          ->getQuery()
                          ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'finished' => (int) $finished,
            'in_progress' => (int) $inProgress,
            'pending' => (int) $pending,
            'byes' => (int) $byes,
            'average_duration' => $avgDuration ? round((float) $avgDuration, 1) : null,
        ];
    }

    /**
     * Trouve les matchs avec filtres
     */
    public function findWithFilters(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.tournament', 't')
            ->join('m.round', 'r');

        // Filtre par tournoi
        if (isset($filters['tournament_id'])) {
            $qb->andWhere('t.id = :tournamentId')
               ->setParameter('tournamentId', $filters['tournament_id']);
        }

        // Filtre par round
        if (isset($filters['round_id'])) {
            $qb->andWhere('r.id = :roundId')
               ->setParameter('roundId', $filters['round_id']);
        }

        // Filtre par numéro de round
        if (isset($filters['round_number'])) {
            $qb->andWhere('r.roundNumber = :roundNumber')
               ->setParameter('roundNumber', $filters['round_number']);
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filtre par numéro de table
        if (isset($filters['table_number'])) {
            $qb->andWhere('m.tableNumber = :tableNumber')
               ->setParameter('tableNumber', $filters['table_number']);
        }

        // Filtre par joueur
        if (isset($filters['player_id'])) {
            $qb->andWhere('m.player1 = :playerId OR m.player2 = :playerId')
               ->setParameter('playerId', $filters['player_id']);
        }

        // Filtre par durée minimale
        if (isset($filters['min_duration'])) {
            $qb->andWhere('m.duration >= :minDuration')
               ->setParameter('minDuration', $filters['min_duration']);
        }

        // Filtre par durée maximale
        if (isset($filters['max_duration'])) {
            $qb->andWhere('m.duration <= :maxDuration')
               ->setParameter('maxDuration', $filters['max_duration']);
        }

        // Filtre par dates
        if (isset($filters['started_after'])) {
            $qb->andWhere('m.startedAt >= :startedAfter')
               ->setParameter('startedAfter', $filters['started_after']);
        }

        if (isset($filters['finished_before'])) {
            $qb->andWhere('m.finishedAt <= :finishedBefore')
               ->setParameter('finishedBefore', $filters['finished_before']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'createdAt';
        $orderDirection = $filters['order_direction'] ?? 'ASC';
        
        if ($orderBy === 'round_number') {
            $qb->orderBy('r.roundNumber', $orderDirection);
        } elseif ($orderBy === 'table_number') {
            $qb->orderBy('m.tableNumber', $orderDirection);
        } else {
            $qb->orderBy('m.' . $orderBy, $orderDirection);
        }

        // Pagination
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les head-to-head entre deux joueurs
     */
    public function findHeadToHead(EventRegistration $player1, EventRegistration $player2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.player1 = :player1 AND m.player2 = :player2)')
            ->orWhere('(m.player1 = :player2 AND m.player2 = :player1)')
            ->setParameter('player1', $player1)
            ->setParameter('player2', $player2)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si deux joueurs se sont déjà affrontés dans le tournoi
     */
    public function havePlayersMetInTournament(EventRegistration $player1, EventRegistration $player2, Tournament $tournament): bool
    {
        $match = $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('(m.player1 = :player1 AND m.player2 = :player2)')
            ->orWhere('(m.player1 = :player2 AND m.player2 = :player1)')
            ->setParameter('tournament', $tournament)
            ->setParameter('player1', $player1)
            ->setParameter('player2', $player2)
            ->getQuery()
            ->getOneOrNullResult();

        return $match !== null;
    }

    /**
     * Trouve les matchs récents (toutes tournois confondus)
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.tournament', 't')
            ->where('m.status = :finished')
            ->setParameter('finished', TournamentMatch::STATUS_FINISHED)
            ->orderBy('m.finishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Assigne automatiquement les numéros de table pour un round
     */
    public function assignTableNumbers(TournamentRound $round): void
    {
        $matches = $this->findByRound($round);
        
        foreach ($matches as $index => $match) {
            if (!$match->hasBye()) { // Les byes n'ont pas besoin de table
                $match->setTableNumber($index + 1);
            }
        }
        
        $this->getEntityManager()->flush();
    }
}