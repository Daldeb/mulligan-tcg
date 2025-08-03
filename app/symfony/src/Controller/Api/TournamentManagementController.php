<?php

namespace App\Controller\Api;

use App\Entity\Tournament;
use App\Entity\TournamentRound;
use App\Entity\TournamentMatch;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Repository\TournamentRoundRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tournament-management', name: 'api_tournament_management_')]
class TournamentManagementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournamentRepository $tournamentRepository,
        private TournamentRoundRepository $roundRepository,
        private TournamentMatchRepository $matchRepository,
        private EventRegistrationRepository $registrationRepository,
        private ValidatorInterface $validator
    ) {}

    /**
     * Dashboard organisateur - Vue d'ensemble du tournoi
     * GET /api/tournament-management/{id}/dashboard
     */
    #[Route('/{id}/dashboard', name: 'dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $currentRound = $this->roundRepository->findCurrentRound($tournament);
        $activeMatches = [];
        $pendingMatches = [];
        $overtimeMatches = [];
        
        if ($currentRound) {
            $activeMatches = $this->matchRepository->findInProgressByTournament($tournament);
            $pendingMatches = $this->matchRepository->findPendingByRound($currentRound);
            $overtimeMatches = $this->matchRepository->findRequiringAction($tournament, 60);
        }

        // Statistiques temps réel
        $registrationStats = $this->registrationRepository->getRegistrationStats($tournament);
        $matchStats = $this->matchRepository->getMatchStats($tournament);

        return $this->json([
            'tournament' => $this->serializeTournamentOverview($tournament),
            'current_round' => $currentRound ? $this->serializeRoundForManagement($currentRound) : null,
            'active_matches' => array_map([$this, 'serializeMatchForManagement'], $activeMatches),
            'pending_matches' => array_map([$this, 'serializeMatchForManagement'], $pendingMatches),
            'overtime_matches' => array_map([$this, 'serializeMatchForManagement'], $overtimeMatches),
            'stats' => [
                'registration' => $registrationStats,
                'matches' => $matchStats,
                'progress' => $tournament->getProgress()
            ],
            'urgent_actions' => $this->getUrgentActions($tournament)
        ]);
    }

    /**
     * Démarrer un match spécifique
     * POST /api/tournament-management/matches/{matchId}/start
     */
    #[Route('/matches/{matchId}/start', name: 'start_match', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startMatch(int $matchId): JsonResponse
    {
        $match = $this->matchRepository->find($matchId);

        if (!$match) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($match->getTournament(), $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$match->canStart()) {
            return $this->json(['error' => 'Ce match ne peut pas être démarré'], 400);
        }

        try {
            $match->start();
            $this->em->flush();

            return $this->json([
                'message' => 'Match démarré avec succès',
                'match' => $this->serializeMatchForManagement($match)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du démarrage: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Saisir le résultat d'un match
     * POST /api/tournament-management/matches/{matchId}/result
     */
    #[Route('/matches/{matchId}/result', name: 'submit_result', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitResult(int $matchId, Request $request): JsonResponse
    {
        $match = $this->matchRepository->find($matchId);

        if (!$match) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($match->getTournament(), $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$match->canFinish()) {
            return $this->json(['error' => 'Ce match ne peut pas être terminé'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['player1_score'], $data['player2_score'])) {
            return $this->json(['error' => 'Scores des joueurs requis'], 400);
        }

        $player1Score = (int) $data['player1_score'];
        $player2Score = (int) $data['player2_score'];

        // Validation des scores
        if ($player1Score < 0 || $player2Score < 0) {
            return $this->json(['error' => 'Les scores ne peuvent pas être négatifs'], 400);
        }

        if ($player1Score === $player2Score && $player1Score === 0) {
            return $this->json(['error' => 'Score 0-0 non autorisé'], 400);
        }

        try {
            // Gestion détaillée des games si fournie
            if (isset($data['game_results']) && is_array($data['game_results'])) {
                $match->setGameResults($data['game_results']);
            }

            // Notes du match si fournies
            if (isset($data['notes'])) {
                $match->setNotes($data['notes']);
            }

            // Terminer le match
            $match->finish($player1Score, $player2Score);

            // Mettre à jour les stats des joueurs
            $this->updatePlayerStats($match);

            // Vérifier si le round est terminé
            $round = $match->getRound();
            $round->updateMatchesStatus();

            $this->em->flush();

            $response = [
                'message' => 'Résultat enregistré avec succès',
                'match' => $this->serializeMatchForManagement($match),
                'round_completed' => $round->isFinished()
            ];

            // Si le round est terminé, proposer actions suivantes
            if ($round->isFinished()) {
                $response['next_actions'] = $this->getNextRoundActions($match->getTournament(), $round);
            }

            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Modifier le résultat d'un match (correction)
     * PUT /api/tournament-management/matches/{matchId}/result
     */
    #[Route('/matches/{matchId}/result', name: 'update_result', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateResult(int $matchId, Request $request): JsonResponse
    {
        $match = $this->matchRepository->find($matchId);

        if (!$match) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($match->getTournament(), $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$match->isFinished()) {
            return $this->json(['error' => 'Seuls les matchs terminés peuvent être modifiés'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['player1_score'], $data['player2_score'])) {
            return $this->json(['error' => 'Scores des joueurs requis'], 400);
        }

        try {
            $oldWinner = $match->getWinner();
            
            // Mise à jour du résultat
            $player1Score = (int) $data['player1_score'];
            $player2Score = (int) $data['player2_score'];
            
            $match->setPlayer1Score($player1Score);
            $match->setPlayer2Score($player2Score);

            // Recalcul du gagnant
            if ($player1Score > $player2Score) {
                $match->setWinner($match->getPlayer1());
            } elseif ($player2Score > $player1Score) {
                $match->setWinner($match->getPlayer2());
            } else {
                $match->setWinner(null); // Match nul
            }

            $newWinner = $match->getWinner();

            // Si le gagnant a changé, recalculer les stats
            if ($oldWinner !== $newWinner) {
                $this->recalculatePlayerStats($match, $oldWinner, $newWinner);
            }

            // Notes de correction
            if (isset($data['correction_note'])) {
                $existingNotes = $match->getNotes() ?? '';
                $correctionNote = "[CORRECTION] " . $data['correction_note'];
                $match->setNotes($existingNotes . "\n" . $correctionNote);
            }

            $this->em->flush();

            return $this->json([
                'message' => 'Résultat modifié avec succès',
                'match' => $this->serializeMatchForManagement($match),
                'winner_changed' => $oldWinner !== $newWinner
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la modification: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Disqualifier un joueur d'un match
     * POST /api/tournament-management/matches/{matchId}/disqualify
     */
    #[Route('/matches/{matchId}/disqualify', name: 'disqualify_player', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disqualifyPlayer(int $matchId, Request $request): JsonResponse
    {
        $match = $this->matchRepository->find($matchId);

        if (!$match) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($match->getTournament(), $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['player_id'], $data['reason'])) {
            return $this->json(['error' => 'ID du joueur et raison requis'], 400);
        }

        $playerId = (int) $data['player_id'];
        $reason = $data['reason'];

        // Déterminer quel joueur disqualifier
        $disqualifiedPlayer = null;
        $winner = null;

        if ($match->getPlayer1() && $match->getPlayer1()->getId() === $playerId) {
            $disqualifiedPlayer = $match->getPlayer1();
            $winner = $match->getPlayer2();
        } elseif ($match->getPlayer2() && $match->getPlayer2()->getId() === $playerId) {
            $disqualifiedPlayer = $match->getPlayer2();
            $winner = $match->getPlayer1();
        }

        if (!$disqualifiedPlayer) {
            return $this->json(['error' => 'Joueur non trouvé dans ce match'], 400);
        }

        try {
            // Disqualifier le joueur
            $disqualifiedPlayer->disqualify();
            
            // Résultat par disqualification
            if ($winner === $match->getPlayer1()) {
                $match->finish(2, 0);
            } else {
                $match->finish(0, 2);
            }

            // Notes de disqualification
            $disqualificationNote = "[DISQUALIFICATION] Joueur: " . $disqualifiedPlayer->getUser()->getPseudo() . 
                                   " - Raison: " . $reason;
            $match->setNotes($disqualificationNote);

            $this->em->flush();

            return $this->json([
                'message' => 'Joueur disqualifié avec succès',
                'match' => $this->serializeMatchForManagement($match),
                'disqualified_player' => [
                    'id' => $disqualifiedPlayer->getId(),
                    'user' => $disqualifiedPlayer->getUser()->getPseudo(),
                    'reason' => $reason
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la disqualification: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mettre en pause un tournoi
     * POST /api/tournament-management/{id}/pause
     */
    #[Route('/{id}/pause', name: 'pause_tournament', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function pauseTournament(int $id, Request $request): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $reason = $data['reason'] ?? 'Pause organisateur';

        try {
            // Marquer le tournoi en pause via additionalData
            $additionalData = $tournament->getAdditionalData() ?? [];
            $additionalData['paused'] = true;
            $additionalData['paused_at'] = (new \DateTimeImmutable())->format('c');
            $additionalData['pause_reason'] = $reason;
            $additionalData['paused_by'] = $user->getPseudo();
            $tournament->setAdditionalData($additionalData);

            $this->em->flush();

            return $this->json([
                'message' => 'Tournoi mis en pause',
                'tournament' => [
                    'id' => $tournament->getId(),
                    'title' => $tournament->getTitle(),
                    'paused' => true,
                    'pause_reason' => $reason
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise en pause: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reprendre un tournoi en pause
     * POST /api/tournament-management/{id}/resume
     */
    #[Route('/{id}/resume', name: 'resume_tournament', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resumeTournament(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        try {
            // Reprendre le tournoi
            $additionalData = $tournament->getAdditionalData() ?? [];
            unset($additionalData['paused']);
            unset($additionalData['paused_at']);
            unset($additionalData['pause_reason']);
            unset($additionalData['paused_by']);
            
            $additionalData['resumed_at'] = (new \DateTimeImmutable())->format('c');
            $additionalData['resumed_by'] = $user->getPseudo();
            $tournament->setAdditionalData($additionalData);

            $this->em->flush();

            return $this->json([
                'message' => 'Tournoi repris',
                'tournament' => [
                    'id' => $tournament->getId(),
                    'title' => $tournament->getTitle(),
                    'paused' => false
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la reprise: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Monitoring en temps réel - Statut général
     * GET /api/tournament-management/{id}/monitor
     */
    #[Route('/{id}/monitor', name: 'monitor', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function monitor(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $currentRound = $this->roundRepository->findCurrentRound($tournament);
        $additionalData = $tournament->getAdditionalData() ?? [];
        
        return $this->json([
            'tournament_status' => [
                'id' => $tournament->getId(),
                'current_phase' => $tournament->getCurrentPhase(),
                'current_round' => $tournament->getCurrentRound(),
                'progress' => $tournament->getProgress(),
                'paused' => $additionalData['paused'] ?? false,
                'participants_count' => $tournament->getCurrentParticipants()
            ],
            'round_status' => $currentRound ? [
                'id' => $currentRound->getId(),
                'round_number' => $currentRound->getRoundNumber(),
                'status' => $currentRound->getStatus(),
                'progress' => $currentRound->getProgress(),
                'time_remaining' => $currentRound->getRemainingTime(),
                'is_overtime' => $currentRound->isOvertime()
            ] : null,
            'matches_summary' => $this->getMatchesSummary($tournament),
            'alerts' => $this->getActiveAlerts($tournament),
            'last_updated' => (new \DateTimeImmutable())->format('c')
        ]);
    }

    /**
     * Actions d'urgence disponibles
     * GET /api/tournament-management/{id}/urgent-actions
     */
    #[Route('/{id}/urgent-actions', name: 'urgent_actions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function urgentActions(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        return $this->json([
            'urgent_actions' => $this->getUrgentActions($tournament)
        ]);
    }

    /**
     * Forcer la fin d'un round (administrateur)
     * POST /api/tournament-management/{id}/rounds/{roundId}/force-finish
     */
    #[Route('/{id}/rounds/{roundId}/force-finish', name: 'force_finish_round', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function forceFinishRound(int $id, int $roundId, Request $request): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        $round = $this->roundRepository->find($roundId);

        if (!$tournament || !$round || $round->getTournament() !== $tournament) {
            return $this->json(['error' => 'Tournoi ou round non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $reason = $data['reason'] ?? 'Fin forcée par administrateur';

        try {
            // Terminer tous les matchs en cours avec des résultats par défaut
            $pendingMatches = $this->matchRepository->findPendingByRound($round);
            $inProgressMatches = $this->matchRepository->findInProgressByTournament($tournament);

            foreach (array_merge($pendingMatches, $inProgressMatches) as $match) {
                if ($match->getRound() === $round) {
                    // Résultat par défaut : 0-0 (match nul forcé)
                    $match->finish(0, 0);
                    $match->setNotes('[FORCÉ] ' . $reason);
                }
            }

            // Forcer la fin du round
            $round->setAllMatchesFinished(true);
            $round->finish();

            $this->em->flush();

            return $this->json([
                'message' => 'Round terminé de force',
                'round' => $this->serializeRoundForManagement($round),
                'forced_matches' => count($pendingMatches) + count($inProgressMatches)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la fin forcée: ' . $e->getMessage()], 500);
        }
    }

    // ============= MÉTHODES PRIVÉES =============

    private function canManageTournament(Tournament $tournament, User $user): bool
    {
        // Admin peut tout gérer
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Créateur peut gérer
        if ($tournament->getCreatedBy() === $user) {
            return true;
        }

        // Propriétaire boutique peut gérer tournois de sa boutique
        if ($tournament->getOrganizerType() === Tournament::ORGANIZER_SHOP && 
            $user->hasShop() && 
            $tournament->getOrganizerId() === $user->getShop()->getId()) {
            return true;
        }

        return false;
    }

    private function updatePlayerStats(TournamentMatch $match): void
    {
        if (!$match->isFinished()) {
            return;
        }

        $player1 = $match->getPlayer1();
        $player2 = $match->getPlayer2();
        $winner = $match->getWinner();

        // Mise à jour player1
        if ($player1) {
            $won = ($winner === $player1);
            $draw = ($winner === null);
            $player1->addMatchResult($won, $draw);
            
            // Game points basés sur le score
            $player1->addGamePoints($match->getPlayer1Score() ?? 0);
        }

        // Mise à jour player2 (si pas bye)
        if ($player2) {
            $won = ($winner === $player2);
            $draw = ($winner === null);
            $player2->addMatchResult($won, $draw);
            
            // Game points basés sur le score
            $player2->addGamePoints($match->getPlayer2Score() ?? 0);
        }
    }

    private function recalculatePlayerStats(TournamentMatch $match, ?EventRegistration $oldWinner, ?EventRegistration $newWinner): void
    {
        // Cette méthode devrait recalculer complètement les stats
        // Pour l'instant, implémentation simplifiée
        // TODO: Implémenter la logique complète de recalcul
        
        if ($oldWinner && $oldWinner !== $newWinner) {
            // Retirer une victoire à l'ancien gagnant
            $stats = $oldWinner->getTournamentStats();
            $stats['wins'] = max(0, ($stats['wins'] ?? 0) - 1);
            $stats['match_points'] = max(0, ($stats['match_points'] ?? 0) - 3);
            $oldWinner->setTournamentStats($stats);
        }

        if ($newWinner && $newWinner !== $oldWinner) {
            // Ajouter une victoire au nouveau gagnant
            $newWinner->addMatchResult(true, false);
        }
    }

    private function getUrgentActions(Tournament $tournament): array
    {
        $actions = [];
        $additionalData = $tournament->getAdditionalData() ?? [];
        
        // Vérifier les matchs en overtime
        $overtimeMatches = $this->matchRepository->findRequiringAction($tournament, 30);
        if (!empty($overtimeMatches)) {
            $actions[] = [
                'type' => 'overtime_matches',
                'priority' => 'high',
                'message' => count($overtimeMatches) . ' match(s) en dépassement de temps',
                'count' => count($overtimeMatches)
            ];
        }

        // Vérifier si le tournoi est en pause
        if ($additionalData['paused'] ?? false) {
            $actions[] = [
                'type' => 'tournament_paused',
                'priority' => 'medium',
                'message' => 'Tournoi en pause',
                'reason' => $additionalData['pause_reason'] ?? ''
            ];
        }

        // Vérifier les rounds en overtime
        $currentRound = $this->roundRepository->findCurrentRound($tournament);
        if ($currentRound && $currentRound->isOvertime()) {
            $remainingTime = $currentRound->getRemainingTime();
            $overtimeMinutes = $remainingTime ? abs($remainingTime) : 0;
            
            $actions[] = [
                'type' => 'round_overtime',
                'priority' => 'medium',
                'message' => 'Round en dépassement de temps',
                'overtime_minutes' => $overtimeMinutes
            ];
        }

        return $actions;
    }

    private function getNextRoundActions(Tournament $tournament, TournamentRound $completedRound): array
    {
        $actions = [];
        
        if ($completedRound->getRoundType() === TournamentRound::TYPE_SWISS) {
            if ($completedRound->getRoundNumber() < $tournament->getSwissRounds()) {
                $actions[] = [
                    'action' => 'create_next_swiss_round',
                    'label' => 'Créer le round Swiss suivant',
                    'round_number' => $completedRound->getRoundNumber() + 1
                ];
            } elseif ($tournament->needsTopCut()) {
                $actions[] = [
                    'action' => 'start_top_cut',
                    'label' => 'Démarrer le Top Cut',
                    'top_cut_size' => $tournament->getTopCutSize()
                ];
            } else {
                $actions[] = [
                    'action' => 'finish_tournament',
                    'label' => 'Terminer le tournoi'
                ];
            }
        } else {
            // Round Top Cut
            $remainingPlayers = $this->countRemainingTopCutPlayers($completedRound);
            if ($remainingPlayers > 1) {
                $actions[] = [
                    'action' => 'create_next_top_cut_round',
                    'label' => 'Créer le round Top Cut suivant',
                    'remaining_players' => $remainingPlayers
                ];
            } else {
                $actions[] = [
                    'action' => 'finish_tournament',
                    'label' => 'Terminer le tournoi'
                ];
            }
        }
        
        return $actions;
    }

    private function countRemainingTopCutPlayers(TournamentRound $round): int
    {
        $matches = $this->matchRepository->findByRound($round);
        $winners = 0;
        
        foreach ($matches as $match) {
            if ($match->isFinished() && $match->getWinner()) {
                $winners++;
            }
        }
        
        return $winners;
    }

    private function getMatchesSummary(Tournament $tournament): array
    {
        $currentRound = $this->roundRepository->findCurrentRound($tournament);
        
        if (!$currentRound) {
            return [
                'total' => 0,
                'finished' => 0,
                'in_progress' => 0,
                'pending' => 0
            ];
        }

        return [
            'total' => $currentRound->getMatchesCount(),
            'finished' => $currentRound->getFinishedMatchesCount(),
            'in_progress' => $currentRound->getInProgressMatchesCount(),
            'pending' => $currentRound->getPendingMatchesCount()
        ];
    }

    private function getActiveAlerts(Tournament $tournament): array
    {
        $alerts = [];
        
        // Alert overtime matches
        $overtimeMatches = $this->matchRepository->findRequiringAction($tournament, 45);
        foreach ($overtimeMatches as $match) {
            $alerts[] = [
                'type' => 'match_overtime',
                'severity' => 'warning',
                'message' => 'Match Table ' . $match->getTableNumber() . ' en overtime',
                'match_id' => $match->getId(),
                'table_number' => $match->getTableNumber(),
                'duration_minutes' => $match->getDuration()
            ];
        }

        // Alert participants manquants
        $pendingCheckIn = $this->registrationRepository->findPendingCheckIn($tournament);
        if (!empty($pendingCheckIn) && $tournament->isSwissPhase()) {
            $alerts[] = [
                'type' => 'pending_checkin',
                'severity' => 'info',
                'message' => count($pendingCheckIn) . ' participant(s) non check-in',
                'count' => count($pendingCheckIn)
            ];
        }

        return $alerts;
    }

    private function serializeTournamentOverview(Tournament $tournament): array
    {
        $additionalData = $tournament->getAdditionalData() ?? [];
        
        return [
            'id' => $tournament->getId(),
            'title' => $tournament->getTitle(),
            'current_phase' => $tournament->getCurrentPhase(),
            'current_round' => $tournament->getCurrentRound(),
            'swiss_rounds' => $tournament->getSwissRounds(),
            'top_cut_size' => $tournament->getTopCutSize(),
            'participants_count' => $tournament->getCurrentParticipants(),
            'progress' => $tournament->getProgress(),
            'started_at' => $tournament->getStartedAt()?->format('c'),
            'paused' => $additionalData['paused'] ?? false,
            'game_format' => [
                'name' => $tournament->getGameFormat()?->getFullName(),
                'game' => $tournament->getGameFormat()?->getGame()?->getName()
            ]
        ];
    }

    private function serializeRoundForManagement(TournamentRound $round): array
    {
        return [
            'id' => $round->getId(),
            'round_number' => $round->getRoundNumber(),
            'round_type' => $round->getRoundType(),
            'status' => $round->getStatus(),
            'started_at' => $round->getStartedAt()?->format('c'),
            'time_limit' => $round->getTimeLimit(),
            'time_remaining' => $round->getRemainingTime(),
            'is_overtime' => $round->isOvertime(),
            'progress' => $round->getProgress(),
            'matches_count' => $round->getMatchesCount(),
            'finished_matches' => $round->getFinishedMatchesCount(),
            'pending_matches' => $round->getPendingMatchesCount(),
            'in_progress_matches' => $round->getInProgressMatchesCount()
        ];
    }

    private function serializeMatchForManagement(TournamentMatch $match): array
    {
        return [
            'id' => $match->getId(),
            'table_number' => $match->getTableNumber(),
            'status' => $match->getStatus(),
            'round_number' => $match->getRound()->getRoundNumber(),
            'player1' => $match->getPlayer1() ? [
                'id' => $match->getPlayer1()->getId(),
                'user_id' => $match->getPlayer1()->getUser()->getId(),
                'pseudo' => $match->getPlayer1()->getUser()->getPseudo(),
                'seed' => $match->getPlayer1()->getSeedNumber(),
                'status' => $match->getPlayer1()->getStatus()
            ] : null,
            'player2' => $match->getPlayer2() ? [
                'id' => $match->getPlayer2()->getId(),
                'user_id' => $match->getPlayer2()->getUser()->getId(),
                'pseudo' => $match->getPlayer2()->getUser()->getPseudo(),
                'seed' => $match->getPlayer2()->getSeedNumber(),
                'status' => $match->getPlayer2()->getStatus()
            ] : null,
            'winner_id' => $match->getWinner()?->getId(),
            'score' => $match->getScoreDisplay(),
            'player1_score' => $match->getPlayer1Score(),
            'player2_score' => $match->getPlayer2Score(),
            'started_at' => $match->getStartedAt()?->format('c'),
            'finished_at' => $match->getFinishedAt()?->format('c'),
            'duration' => $match->getDuration(),
            'is_bye' => $match->isBye(),
            'notes' => $match->getNotes(),
            'game_results' => $match->getGameResults(),
            'actions' => $this->getMatchActions($match)
        ];
    }

    private function getMatchActions(TournamentMatch $match): array
    {
        $actions = [];

        if ($match->canStart()) {
            $actions[] = [
                'action' => 'start',
                'label' => 'Démarrer le match',
                'method' => 'POST',
                'endpoint' => '/api/tournament-management/matches/' . $match->getId() . '/start'
            ];
        }

        if ($match->canFinish()) {
            $actions[] = [
                'action' => 'finish',
                'label' => 'Saisir le résultat',
                'method' => 'POST',
                'endpoint' => '/api/tournament-management/matches/' . $match->getId() . '/result'
            ];
        }

        if ($match->isFinished()) {
            $actions[] = [
                'action' => 'edit_result',
                'label' => 'Modifier le résultat',
                'method' => 'PUT',
                'endpoint' => '/api/tournament-management/matches/' . $match->getId() . '/result'
            ];
        }

        if ($match->isInProgress() || $match->isPending()) {
            $actions[] = [
                'action' => 'disqualify',
                'label' => 'Disqualifier un joueur',
                'method' => 'POST',
                'endpoint' => '/api/tournament-management/matches/' . $match->getId() . '/disqualify'
            ];
        }

        return $actions;
    }
}