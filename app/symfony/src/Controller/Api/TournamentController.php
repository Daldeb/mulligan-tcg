<?php

namespace App\Controller\Api;

use App\Entity\Tournament;
use App\Entity\TournamentRound;
use App\Entity\TournamentMatch;
use App\Entity\User;
use App\Repository\TournamentRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\TournamentRoundRepository;
use App\Repository\TournamentMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tournaments', name: 'api_tournaments_')]
class TournamentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournamentRepository $tournamentRepository,
        private EventRegistrationRepository $registrationRepository,
        private TournamentRoundRepository $roundRepository,
        private TournamentMatchRepository $matchRepository
    ) {}

    /**
     * Liste des tournois avec filtres spécifiques
     * GET /api/tournaments
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $filters = $this->buildTournamentFilters($request);
        
        if (!$this->isGranted('ROLE_ADMIN')) {
            $filters['include_hidden'] = false;
        }

        $tournaments = $this->tournamentRepository->findWithTournamentFilters($filters, $limit, $offset);
        $total = $this->tournamentRepository->countWithTournamentFilters($filters);

        return $this->json([
            'tournaments' => array_map([$this, 'serializeTournament'], $tournaments),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Détails complets d'un tournoi
     * GET /api/tournaments/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        if (!$this->isGranted('ROLE_ADMIN') && !$tournament->isVisible()) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        return $this->json([
            'tournament' => $this->serializeTournamentDetailed($tournament)
        ]);
    }

    /**
     * Démarrer un tournoi
     * POST /api/tournaments/{id}/start
     */
    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startTournament(int $id): JsonResponse
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

        if (!$tournament->canStart()) {
            return $this->json(['error' => 'Le tournoi ne peut pas être démarré'], 400);
        }

        try {
            // Assignation des seed numbers aux participants confirmés
            $this->registrationRepository->assignSeedNumbers($tournament);

            // Démarrage du tournoi
            $tournament->startTournament();

            // Calcul automatique des paramètres
            $tournament->calculateTopCutSize();

            // Création du premier round Swiss
            $firstRound = $this->createSwissRound($tournament, 1);

            $this->em->flush();

            return $this->json([
                'message' => 'Tournoi démarré avec succès',
                'tournament' => $this->serializeTournamentDetailed($tournament),
                'first_round' => $this->serializeRound($firstRound)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du démarrage: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Générer les pairings pour un round
     * POST /api/tournaments/{id}/rounds/{roundId}/generate-pairings
     */
    #[Route('/{id}/rounds/{roundId}/generate-pairings', name: 'generate_pairings', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generatePairings(int $id, int $roundId): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        $round = $this->roundRepository->find($roundId);

        if (!$tournament || !$round || $round->getTournament() !== $tournament) {
            return $this->json(['error' => 'Tournoi ou round non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$round->canGeneratePairings()) {
            return $this->json(['error' => 'Les pairings ne peuvent pas être générés pour ce round'], 400);
        }

        try {
            $participants = $this->getActiveParticipants($tournament);
            
            if ($round->getRoundType() === TournamentRound::TYPE_SWISS) {
                $matches = $this->generateSwissPairings($round, $participants);
            } else {
                $matches = $this->generateTopCutPairings($round, $participants);
            }

            // Assignation des numéros de table
            $this->matchRepository->assignTableNumbers($round);

            $round->setPairingsGenerated(true);
            $round->setPairingsGeneratedAt(new \DateTimeImmutable());

            $this->em->flush();

            return $this->json([
                'message' => 'Pairings générés avec succès',
                'round' => $this->serializeRoundDetailed($round),
                'matches' => array_map([$this, 'serializeMatch'], $matches)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la génération: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Démarrer un round
     * POST /api/tournaments/{id}/rounds/{roundId}/start
     */
    #[Route('/{id}/rounds/{roundId}/start', name: 'start_round', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startRound(int $id, int $roundId): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        $round = $this->roundRepository->find($roundId);

        if (!$tournament || !$round || $round->getTournament() !== $tournament) {
            return $this->json(['error' => 'Tournoi ou round non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$round->canStart()) {
            return $this->json(['error' => 'Le round ne peut pas être démarré'], 400);
        }

        try {
            $round->start();
            $tournament->setCurrentRound($round->getRoundNumber());
            
            $this->em->flush();

            return $this->json([
                'message' => 'Round démarré avec succès',
                'round' => $this->serializeRoundDetailed($round)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du démarrage du round: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Terminer un round et passer au suivant
     * POST /api/tournaments/{id}/rounds/{roundId}/finish
     */
    #[Route('/{id}/rounds/{roundId}/finish', name: 'finish_round', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function finishRound(int $id, int $roundId): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        $round = $this->roundRepository->find($roundId);

        if (!$tournament || !$round || $round->getTournament() !== $tournament) {
            return $this->json(['error' => 'Tournoi ou round non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageTournament($tournament, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if (!$round->checkAllMatchesFinished()) {
            return $this->json(['error' => 'Tous les matchs ne sont pas terminés'], 400);
        }

        try {
            $round->finish();
            
            // Mettre à jour les classements après le round
            $this->updateStandings($tournament);

            $nextRound = null;
            
            // Déterminer le prochain round
            if ($round->getRoundType() === TournamentRound::TYPE_SWISS) {
                if ($round->getRoundNumber() < $tournament->getSwissRounds()) {
                    // Prochain round Swiss
                    $nextRound = $this->createSwissRound($tournament, $round->getRoundNumber() + 1);
                } elseif ($tournament->needsTopCut()) {
                    // Démarrer Top Cut
                    $tournament->startTopCut();
                    $nextRound = $this->createTopCutRound($tournament, 1);
                } else {
                    // Fin du tournoi
                    $this->finishTournament($tournament);
                }
            } else {
                // Round Top Cut
                if ($this->hasMoreTopCutRounds($tournament, $round)) {
                    $nextRound = $this->createTopCutRound($tournament, $round->getRoundNumber() + 1);
                } else {
                    // Fin du tournoi
                    $this->finishTournament($tournament);
                }
            }

            $this->em->flush();

            $response = [
                'message' => 'Round terminé avec succès',
                'round' => $this->serializeRoundDetailed($round),
                'tournament' => $this->serializeTournamentDetailed($tournament)
            ];

            if ($nextRound) {
                $response['next_round'] = $this->serializeRound($nextRound);
            }

            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la finalisation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Classements actuels du tournoi
     * GET /api/tournaments/{id}/standings
     */
    #[Route('/{id}/standings', name: 'standings', methods: ['GET'])]
    public function standings(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $standings = $this->registrationRepository->findTournamentStandings($tournament);

        return $this->json([
            'standings' => array_map([$this, 'serializeStanding'], $standings),
            'tournament_info' => [
                'current_phase' => $tournament->getCurrentPhase(),
                'current_round' => $tournament->getCurrentRound(),
                'total_rounds' => $tournament->getSwissRounds(),
                'progress' => $tournament->getProgress()
            ]
        ]);
    }

    /**
     * Rounds d'un tournoi
     * GET /api/tournaments/{id}/rounds
     */
    #[Route('/{id}/rounds', name: 'rounds', methods: ['GET'])]
    public function rounds(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $rounds = $this->roundRepository->findByTournament($tournament);

        return $this->json([
            'rounds' => array_map([$this, 'serializeRound'], $rounds)
        ]);
    }

    /**
     * Détails d'un round avec ses matchs
     * GET /api/tournaments/{id}/rounds/{roundId}
     */
    #[Route('/{id}/rounds/{roundId}', name: 'round_detail', methods: ['GET'])]
    public function roundDetail(int $id, int $roundId): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        $round = $this->roundRepository->find($roundId);

        if (!$tournament || !$round || $round->getTournament() !== $tournament) {
            return $this->json(['error' => 'Tournoi ou round non trouvé'], 404);
        }

        return $this->json([
            'round' => $this->serializeRoundDetailed($round)
        ]);
    }

    /**
     * Statistiques d'un tournoi
     * GET /api/tournaments/{id}/stats
     */
    #[Route('/{id}/stats', name: 'stats', methods: ['GET'])]
    public function stats(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $registrationStats = $this->registrationRepository->getRegistrationStats($tournament);
        $matchStats = $this->matchRepository->getMatchStats($tournament);
        $roundStats = $this->roundRepository->getRoundStats($tournament);

        return $this->json([
            'tournament_stats' => [
                'registration' => $registrationStats,
                'matches' => $matchStats,
                'rounds' => $roundStats,
                'progress' => $tournament->getProgress(),
                'duration' => $tournament->getDuration()?->format('%H:%I'),
                'estimated_duration' => $tournament->getEstimatedDuration()
            ]
        ]);
    }

    /**
     * Tournois par format de jeu
     * GET /api/tournaments/by-format/{formatId}
     */
    #[Route('/by-format/{formatId}', name: 'by_format', methods: ['GET'])]
    public function byFormat(int $formatId): JsonResponse
    {
        $gameFormatRepository = $this->em->getRepository(\App\Entity\GameFormat::class);
        $gameFormat = $gameFormatRepository->find($formatId);

        if (!$gameFormat) {
            return $this->json(['error' => 'Format de jeu non trouvé'], 404);
        }

        $onlyVisible = !$this->isGranted('ROLE_ADMIN');
        $tournaments = $this->tournamentRepository->findByGameFormat($gameFormat, $onlyVisible);

        return $this->json([
            'tournaments' => array_map([$this, 'serializeTournament'], $tournaments),
            'game_format' => [
                'id' => $gameFormat->getId(),
                'name' => $gameFormat->getFullName(),
                'game' => $gameFormat->getGame()->getName()
            ]
        ]);
    }

    /**
     * Tournois ouverts aux inscriptions
     * GET /api/tournaments/open-registration
     */
    #[Route('/open-registration', name: 'open_registration', methods: ['GET'])]
    public function openRegistration(): JsonResponse
    {
        $tournaments = $this->tournamentRepository->findOpenForRegistration();

        return $this->json([
            'tournaments' => array_map([$this, 'serializeTournament'], $tournaments)
        ]);
    }

    /**
     * Tournois en cours
     * GET /api/tournaments/in-progress
     */
    #[Route('/in-progress', name: 'in_progress', methods: ['GET'])]
    public function inProgress(): JsonResponse
    {
        $tournaments = $this->tournamentRepository->findInProgress();

        return $this->json([
            'tournaments' => array_map([$this, 'serializeTournament'], $tournaments)
        ]);
    }

    // ============= MÉTHODES PRIVÉES =============

    private function buildTournamentFilters(Request $request): array
    {
        $filters = [];

        // Filtres spécifiques tournois
        if ($request->query->has('game_format_id')) {
            $filters['game_format_id'] = (int) $request->query->get('game_format_id');
        }

        if ($request->query->has('tournament_format')) {
            $filters['tournament_format'] = $request->query->get('tournament_format');
        }

        if ($request->query->has('current_phase')) {
            $filters['current_phase'] = $request->query->get('current_phase');
        }

        if ($request->query->has('min_participants')) {
            $filters['min_participants'] = (int) $request->query->get('min_participants');
        }

        if ($request->query->has('require_decklist')) {
            $filters['require_decklist'] = filter_var($request->query->get('require_decklist'), FILTER_VALIDATE_BOOLEAN);
        }

        // Filtres généraux d'événements
        if ($request->query->has('start_date')) {
            try {
                $filters['start_date'] = new \DateTimeImmutable($request->query->get('start_date'));
            } catch (\Exception $e) {
                // Ignorer si date invalide
            }
        }

        return $filters;
    }

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

    private function createSwissRound(Tournament $tournament, int $roundNumber): TournamentRound
    {
        $round = new TournamentRound();
        $round->setTournament($tournament);
        $round->setRoundNumber($roundNumber);
        $round->setRoundType(TournamentRound::TYPE_SWISS);
        $round->setTimeLimit($tournament->getMatchTimer());

        $this->em->persist($round);
        
        return $round;
    }

    private function createTopCutRound(Tournament $tournament, int $roundNumber): TournamentRound
    {
        $round = new TournamentRound();
        $round->setTournament($tournament);
        $round->setRoundNumber($roundNumber);
        $round->setRoundType(TournamentRound::TYPE_TOP_CUT);
        $round->setTimeLimit($tournament->getMatchTimer());

        $this->em->persist($round);
        
        return $round;
    }

    private function getActiveParticipants(Tournament $tournament): array
    {
        return $this->registrationRepository->findConfirmedParticipants($tournament);
    }

    private function generateSwissPairings(TournamentRound $round, array $participants): array
    {
        $matches = [];
        
        if ($round->getRoundNumber() === 1) {
            // Premier round : pairings aléatoires basés sur seed
            $shuffled = $participants;
            shuffle($shuffled);
        } else {
            // Rounds suivants : pairings basés sur les points
            $participants = $this->sortParticipantsByPoints($participants);
        }

        // Créer les pairings
        for ($i = 0; $i < count($participants); $i += 2) {
            $player1 = $participants[$i];
            $player2 = $participants[$i + 1] ?? null; // null = bye

            $match = new TournamentMatch();
            $match->setTournament($round->getTournament());
            $match->setRound($round);
            $match->setPlayer1($player1);
            $match->setPlayer2($player2);

            if (!$player2) {
                // Bye automatique
                $match->setStatus(TournamentMatch::STATUS_BYE);
                $match->setWinner($player1);
                $match->setPlayer1Score(2);
                $match->setPlayer2Score(0);
            }

            $this->em->persist($match);
            $matches[] = $match;
        }

        return $matches;
    }

    private function generateTopCutPairings(TournamentRound $round, array $participants): array
    {
        $matches = [];
        $tournament = $round->getTournament();

        if ($round->getRoundNumber() === 1) {
            // Premier round Top Cut : prendre les X meilleurs
            $topPlayers = array_slice($participants, 0, $tournament->getTopCutSize());
            
            // Élimination directe : 1 vs 8, 2 vs 7, etc.
            $pairings = $this->createEliminationPairings($topPlayers);
        } else {
            // Rounds suivants : gagnants du round précédent
            $pairings = $this->getTopCutWinners($round);
        }

        foreach ($pairings as $pairing) {
            $match = new TournamentMatch();
            $match->setTournament($tournament);
            $match->setRound($round);
            $match->setPlayer1($pairing['player1']);
            $match->setPlayer2($pairing['player2']);

            $this->em->persist($match);
            $matches[] = $match;
        }

        return $matches;
    }

    private function createEliminationPairings(array $players): array
    {
        $pairings = [];
        $count = count($players);
        
        for ($i = 0; $i < $count / 2; $i++) {
            $pairings[] = [
                'player1' => $players[$i],
                'player2' => $players[$count - 1 - $i]
            ];
        }
        
        return $pairings;
    }

    private function sortParticipantsByPoints(array $participants): array
    {
        usort($participants, function($a, $b) {
            $aPoints = $a->getMatchPoints();
            $bPoints = $b->getMatchPoints();
            
            if ($aPoints === $bPoints) {
                // Tiebreaker par OMW%
                return $b->getTournamentStatValue('opponent_match_win_percentage', 0) <=> 
                       $a->getTournamentStatValue('opponent_match_win_percentage', 0);
            }
            
            return $bPoints <=> $aPoints;
        });
        
        return $participants;
    }

    private function updateStandings(Tournament $tournament): void
    {
        $standings = $this->registrationRepository->findTournamentStandings($tournament);
        
        // Calculer tiebreakers et mettre à jour classements
        foreach ($standings as $index => $registration) {
            // TODO: Calculer OMW%, GW%, OGW%
            // Pour l'instant, on stocke juste le classement temporaire
            $registration->setTournamentStatValue('current_rank', $index + 1);
        }

        // Stocker classements dans le tournoi
        $standingsData = array_map(function($reg) {
            return [
                'user_id' => $reg->getUser()->getId(),
                'rank' => $reg->getTournamentStatValue('current_rank'),
                'points' => $reg->getMatchPoints(),
                'record' => $reg->getMatchRecord()
            ];
        }, $standings);

        $tournament->setStandings($standingsData);
    }

    private function hasMoreTopCutRounds(Tournament $tournament, TournamentRound $round): bool
    {
        $remainingPlayers = count($this->getTopCutWinners($round));
        return $remainingPlayers > 1;
    }

    private function getTopCutWinners(TournamentRound $round): array
    {
        // Récupérer les gagnants du round précédent pour le prochain round
        $matches = $this->matchRepository->findFinishedByRound($round);
        $winners = [];
        
        foreach ($matches as $match) {
            if ($match->getWinner()) {
                $winners[] = $match->getWinner();
            }
        }
        
        return $winners;
    }

    private function finishTournament(Tournament $tournament): void
    {
        $tournament->finishTournament();
        
        // Assigner les classements finaux
        $standings = $this->registrationRepository->findTournamentStandings($tournament);
        foreach ($standings as $index => $registration) {
            $registration->setFinalRanking($index + 1);
        }
    }

    private function serializeTournament(Tournament $tournament): array
    {
        return [
            'id' => $tournament->getId(),
            'title' => $tournament->getTitle(),
            'game_format' => [
                'id' => $tournament->getGameFormat()?->getId(),
                'name' => $tournament->getGameFormat()?->getFullName()
            ],
            'tournament_format' => $tournament->getTournamentFormat(),
            'current_phase' => $tournament->getCurrentPhase(),
            'current_round' => $tournament->getCurrentRound(),
            'swiss_rounds' => $tournament->getSwissRounds(),
            'top_cut_size' => $tournament->getTopCutSize(),
            'current_participants' => $tournament->getCurrentParticipants(),
            'max_participants' => $tournament->getMaxParticipants(),
            'start_date' => $tournament->getStartDate()?->format('c'),
            'status' => $tournament->getStatus(),
            'progress' => $tournament->getProgress(),
            'can_register' => $tournament->canRegister()
        ];
    }

    private function serializeTournamentDetailed(Tournament $tournament): array
    {
        $data = $this->serializeTournament($tournament);
        
        $data['description'] = $tournament->getDescription();
        $data['match_timer'] = $tournament->getMatchTimer();
        $data['break_timer'] = $tournament->getBreakTimer();
        $data['require_decklist'] = $tournament->isRequireDecklist();
        $data['allow_decklist'] = $tournament->isAllowDecklist();
        $data['prize_pool'] = $tournament->getPrizePool();
        $data['started_at'] = $tournament->getStartedAt()?->format('c');
        $data['finished_at'] = $tournament->getFinishedAt()?->format('c');
        
        return $data;
    }

    private function serializeRound(TournamentRound $round): array
    {
        return [
            'id' => $round->getId(),
            'round_number' => $round->getRoundNumber(),
            'round_type' => $round->getRoundType(),
            'status' => $round->getStatus(),
            'pairings_generated' => $round->isPairingsGenerated(),
            'all_matches_finished' => $round->isAllMatchesFinished(),
            'started_at' => $round->getStartedAt()?->format('c'),
            'finished_at' => $round->getFinishedAt()?->format('c'),
            'time_limit' => $round->getTimeLimit(),
            'progress' => $round->getProgress(),
            'matches_count' => $round->getMatchesCount()
        ];
    }

    private function serializeRoundDetailed(TournamentRound $round): array
    {
        $data = $this->serializeRound($round);
        
        $matches = $this->matchRepository->findByRound($round);
        $data['matches'] = array_map([$this, 'serializeMatch'], $matches);
        
        return $data;
    }

    private function serializeMatch(TournamentMatch $match): array
    {
        return [
            'id' => $match->getId(),
            'table_number' => $match->getTableNumber(),
            'status' => $match->getStatus(),
            'player1' => $match->getPlayer1() ? [
                'id' => $match->getPlayer1()->getId(),
                'user' => $match->getPlayer1()->getUser()->getPseudo(),
                'seed' => $match->getPlayer1()->getSeedNumber()
            ] : null,
            'player2' => $match->getPlayer2() ? [
                'id' => $match->getPlayer2()->getId(),
                'user' => $match->getPlayer2()->getUser()->getPseudo(),
                'seed' => $match->getPlayer2()->getSeedNumber()
            ] : null,
            'winner_id' => $match->getWinner()?->getId(),
            'score' => $match->getScoreDisplay(),
            'player1_score' => $match->getPlayer1Score(),
            'player2_score' => $match->getPlayer2Score(),
            'started_at' => $match->getStartedAt()?->format('c'),
            'finished_at' => $match->getFinishedAt()?->format('c'),
            'duration' => $match->getDuration(),
            'is_bye' => $match->isBye()
        ];
    }

    private function serializeStanding($registration): array
    {
        return [
            'rank' => $registration->getTournamentStatValue('current_rank', 0),
            'user' => [
                'id' => $registration->getUser()->getId(),
                'pseudo' => $registration->getUser()->getPseudo()
            ],
            'record' => $registration->getMatchRecord(),
            'match_points' => $registration->getMatchPoints(),
            'game_points' => $registration->getGamePoints(),
            'opponent_match_win_percentage' => $registration->getTournamentStatValue('opponent_match_win_percentage', 0),
            'game_win_percentage' => $registration->getWinPercentage(),
            'seed_number' => $registration->getSeedNumber(),
            'final_ranking' => $registration->getFinalRanking()
        ];
    }
}