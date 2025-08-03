<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\TournamentRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\UserRepository;
use App\Repository\ShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/events', name: 'api_admin_events_')]
#[IsGranted('ROLE_ADMIN')]
class EventAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private TournamentRepository $tournamentRepository,
        private EventRegistrationRepository $registrationRepository,
        private UserRepository $userRepository,
        private ShopRepository $shopRepository,
        private ValidatorInterface $validator
    ) {}

    /**
     * Dashboard admin - Vue d'ensemble des événements
     * GET /api/admin/events/dashboard
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        // Statistiques générales
        $stats = [
            'events' => $this->getEventsStats(),
            'tournaments' => $this->getTournamentsStats(),
            'registrations' => $this->getRegistrationsStats(),
            'users' => $this->getUsersStats()
        ];

        // Événements nécessitant action
        $pendingReview = $this->eventRepository->findPendingReview();
        $recentEvents = $this->eventRepository->findRecent(10);
        $upcomingEvents = $this->eventRepository->findUpcoming(10);

        // Alertes admin
        $alerts = $this->getAdminAlerts();

        return $this->json([
            'stats' => $stats,
            'pending_review' => array_map([$this, 'serializeEventForAdmin'], $pendingReview),
            'recent_events' => array_map([$this, 'serializeEventForAdmin'], $recentEvents),
            'upcoming_events' => array_map([$this, 'serializeEventForAdmin'], $upcomingEvents),
            'alerts' => $alerts,
            'last_updated' => (new \DateTimeImmutable())->format('c')
        ]);
    }

    /**
     * Liste des événements en attente de validation
     * GET /api/admin/events/pending-review
     */
    #[Route('/pending-review', name: 'pending_review', methods: ['GET'])]
    public function pendingReview(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $events = $this->eventRepository->findWithFilters([
            'status' => Event::STATUS_PENDING_REVIEW,
            'include_hidden' => true
        ], $limit, $offset);

        $total = $this->eventRepository->countWithFilters([
            'status' => Event::STATUS_PENDING_REVIEW,
            'include_hidden' => true
        ]);

        return $this->json([
            'events' => array_map([$this, 'serializeEventForReview'], $events),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Approuver un événement
     * POST /api/admin/events/{id}/approve
     */
    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        if (!$event->isPendingReview()) {
            return $this->json(['error' => 'Cet événement n\'est pas en attente de validation'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $comment = $data['comment'] ?? null;

        try {
            /** @var User $admin */
            $admin = $this->getUser();
            
            $event->approve($admin, $comment);
            $this->em->flush();

            // TODO: Envoyer notification au créateur
            $this->notifyEventCreator($event, 'approved', $comment);

            return $this->json([
                'message' => 'Événement approuvé avec succès',
                'event' => $this->serializeEventForAdmin($event)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'approbation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Rejeter un événement
     * POST /api/admin/events/{id}/reject
     */
    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        if (!$event->isPendingReview()) {
            return $this->json(['error' => 'Cet événement n\'est pas en attente de validation'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['reason'])) {
            return $this->json(['error' => 'Raison du rejet requise'], 400);
        }

        $reason = $data['reason'];

        try {
            /** @var User $admin */
            $admin = $this->getUser();
            
            $event->reject($admin, $reason);
            $this->em->flush();

            // TODO: Envoyer notification au créateur
            $this->notifyEventCreator($event, 'rejected', $reason);

            return $this->json([
                'message' => 'Événement rejeté',
                'event' => $this->serializeEventForAdmin($event)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du rejet: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Masquer/Afficher un événement
     * POST /api/admin/events/{id}/toggle-visibility
     */
    #[Route('/{id}/toggle-visibility', name: 'toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $reason = $data['reason'] ?? 'Action administrative';

        try {
            /** @var User $admin */
            $admin = $this->getUser();
            
            if ($event->isVisible()) {
                $event->hide($admin, $reason);
                $message = 'Événement masqué';
                $action = 'hidden';
            } else {
                $event->setVisibility(Event::VISIBILITY_VISIBLE);
                $event->setReviewedBy($admin);
                $event->setReviewedAt(new \DateTimeImmutable());
                $event->setReviewComment($reason);
                $message = 'Événement rendu visible';
                $action = 'shown';
            }

            $this->em->flush();

            // Notifier le créateur si nécessaire
            if (!empty($reason) && $reason !== 'Action administrative') {
                $this->notifyEventCreator($event, $action, $reason);
            }

            return $this->json([
                'message' => $message,
                'event' => $this->serializeEventForAdmin($event)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du changement de visibilité: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Forcer l'annulation d'un événement
     * POST /api/admin/events/{id}/force-cancel
     */
    #[Route('/{id}/force-cancel', name: 'force_cancel', methods: ['POST'])]
    public function forceCancel(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        if ($event->isFinished()) {
            return $this->json(['error' => 'Un événement terminé ne peut pas être annulé'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['reason'])) {
            return $this->json(['error' => 'Raison de l\'annulation requise'], 400);
        }

        $reason = $data['reason'];

        try {
            /** @var User $admin */
            $admin = $this->getUser();
            
            $event->cancel();
            $event->setReviewedBy($admin);
            $event->setReviewedAt(new \DateTimeImmutable());
            $event->setReviewComment('[ANNULATION FORCÉE] ' . $reason);

            // Annuler toutes les inscriptions actives
            $activeRegistrations = $this->registrationRepository->findActiveByEvent($event);
            foreach ($activeRegistrations as $registration) {
                $registration->cancel();
            }

            $this->em->flush();

            // Notifier tous les participants
            $this->notifyEventCancellation($event, $reason);

            return $this->json([
                'message' => 'Événement annulé de force',
                'event' => $this->serializeEventForAdmin($event),
                'cancelled_registrations' => count($activeRegistrations)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'annulation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Statistiques détaillées des événements
     * GET /api/admin/events/stats
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '30'); // jours
        $startDate = new \DateTimeImmutable('-' . $period . ' days');

        $stats = [
            'overview' => $this->getEventsStats(),
            'by_period' => $this->getEventStatsByPeriod($startDate),
            'by_type' => $this->getEventStatsByType($startDate),
            'by_organizer' => $this->getEventStatsByOrganizer($startDate),
            'registrations' => [
                'total' => $this->registrationRepository->countTotal(),
                'by_period' => $this->getRegistrationStatsByPeriod($startDate),
                'by_event_type' => $this->getRegistrationStatsByEventType($startDate)
            ],
            'tournaments' => $this->getTournamentDetailedStats($startDate)
        ];

        return $this->json([
            'stats' => $stats,
            'period_days' => (int) $period,
            'generated_at' => (new \DateTimeImmutable())->format('c')
        ]);
    }

    /**
     * Liste des utilisateurs organisateurs
     * GET /api/admin/events/organizers
     */
    #[Route('/organizers', name: 'organizers', methods: ['GET'])]
    public function organizers(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Récupérer les utilisateurs qui ont créé des événements
        $organizers = $this->userRepository->findEventOrganizers($limit, $offset);
        $total = $this->userRepository->countEventOrganizers();

        $organizersData = [];
        foreach ($organizers as $organizer) {
            $eventsCreated = $this->eventRepository->countByCreator($organizer);
            $activeEvents = $this->eventRepository->countActiveByCreator($organizer);
            
            $organizersData[] = [
                'user' => [
                    'id' => $organizer->getId(),
                    'pseudo' => $organizer->getPseudo(),
                    'email' => $organizer->getEmail(),
                    'roles' => $organizer->getRoles(),
                    'created_at' => $organizer->getCreatedAt()->format('c')
                ],
                'stats' => [
                    'events_created' => $eventsCreated,
                    'active_events' => $activeEvents,
                    'has_shop' => $organizer->hasShop(),
                    'shop_name' => $organizer->hasShop() ? $organizer->getShop()->getName() : null
                ]
            ];
        }

        return $this->json([
            'organizers' => $organizersData,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Historique des actions admin sur les événements
     * GET /api/admin/events/audit-log
     */
    #[Route('/audit-log', name: 'audit_log', methods: ['GET'])]
    public function auditLog(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
        $offset = ($page - 1) * $limit;

        // Récupérer les événements avec actions admin récentes
        $events = $this->eventRepository->findWithAdminActions($limit, $offset);
        $total = $this->eventRepository->countWithAdminActions();

        $auditData = [];
        foreach ($events as $event) {
            if ($event->getReviewedBy()) {
                $auditData[] = [
                    'event' => [
                        'id' => $event->getId(),
                        'title' => $event->getTitle(),
                        'type' => $event->getEventType(),
                        'status' => $event->getStatus(),
                        'visibility' => $event->getVisibility()
                    ],
                    'action' => [
                        'admin' => $event->getReviewedBy()->getPseudo(),
                        'date' => $event->getReviewedAt()?->format('c'),
                        'comment' => $event->getReviewComment()
                    ],
                    'creator' => [
                        'pseudo' => $event->getCreatedBy()->getPseudo(),
                        'type' => $event->getOrganizerType()
                    ]
                ];
            }
        }

        return $this->json([
            'audit_log' => $auditData,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Recherche avancée d'événements (admin)
     * GET /api/admin/events/search
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $filters = $this->buildAdminFilters($request);
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        if (strlen($query) >= 2) {
            $events = $this->eventRepository->searchByText($query, false); // Inclure events cachés
        } else {
            $events = $this->eventRepository->findWithFilters($filters, $limit);
        }

        return $this->json([
            'events' => array_map([$this, 'serializeEventForAdmin'], array_slice($events, 0, $limit)),
            'total' => count($events),
            'query' => $query,
            'filters' => $filters
        ]);
    }

    /**
     * Actions en lot sur les événements
     * POST /api/admin/events/batch-actions
     */
    #[Route('/batch-actions', name: 'batch_actions', methods: ['POST'])]
    public function batchActions(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['action'], $data['event_ids']) || !is_array($data['event_ids'])) {
            return $this->json(['error' => 'Action et IDs d\'événements requis'], 400);
        }

        $action = $data['action'];
        $eventIds = $data['event_ids'];
        $reason = $data['reason'] ?? 'Action en lot';

        $validActions = ['approve', 'reject', 'hide', 'show', 'cancel'];
        if (!in_array($action, $validActions)) {
            return $this->json(['error' => 'Action non valide'], 400);
        }

        $events = $this->eventRepository->findBy(['id' => $eventIds]);
        $results = ['success' => 0, 'errors' => []];

        /** @var User $admin */
        $admin = $this->getUser();

        foreach ($events as $event) {
            try {
                switch ($action) {
                    case 'approve':
                        if ($event->isPendingReview()) {
                            $event->approve($admin, $reason);
                            $results['success']++;
                        }
                        break;
                    case 'reject':
                        if ($event->isPendingReview()) {
                            $event->reject($admin, $reason);
                            $results['success']++;
                        }
                        break;
                    case 'hide':
                        if ($event->isVisible()) {
                            $event->hide($admin, $reason);
                            $results['success']++;
                        }
                        break;
                    case 'show':
                        if ($event->isHidden()) {
                            $event->setVisibility(Event::VISIBILITY_VISIBLE);
                            $event->setReviewedBy($admin);
                            $event->setReviewedAt(new \DateTimeImmutable());
                            $results['success']++;
                        }
                        break;
                    case 'cancel':
                        if (!$event->isFinished() && !$event->isCancelled()) {
                            $event->cancel();
                            $event->setReviewedBy($admin);
                            $event->setReviewedAt(new \DateTimeImmutable());
                            $results['success']++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'event_id' => $event->getId(),
                    'error' => $e->getMessage()
                ];
            }
        }

        if ($results['success'] > 0) {
            $this->em->flush();
        }

        return $this->json([
            'message' => $results['success'] . ' événement(s) traité(s)',
            'results' => $results
        ]);
    }

    // ============= MÉTHODES PRIVÉES =============

    private function getEventsStats(): array
    {
        return [
            'total' => $this->eventRepository->countAll(),
            'by_status' => [
                'draft' => $this->eventRepository->countByStatus(Event::STATUS_DRAFT),
                'pending_review' => $this->eventRepository->countByStatus(Event::STATUS_PENDING_REVIEW),
                'approved' => $this->eventRepository->countByStatus(Event::STATUS_APPROVED),
                'rejected' => $this->eventRepository->countByStatus(Event::STATUS_REJECTED),
                'in_progress' => $this->eventRepository->countByStatus(Event::STATUS_IN_PROGRESS),
                'finished' => $this->eventRepository->countByStatus(Event::STATUS_FINISHED),
                'cancelled' => $this->eventRepository->countByStatus(Event::STATUS_CANCELLED)
            ],
            'by_visibility' => [
                'visible' => $this->eventRepository->countByVisibility(Event::VISIBILITY_VISIBLE),
                'hidden' => $this->eventRepository->countByVisibility(Event::VISIBILITY_HIDDEN)
            ]
        ];
    }

    private function getTournamentsStats(): array
    {
        return [
            'total' => $this->tournamentRepository->countAll(),
            'by_phase' => [
                'registration' => $this->tournamentRepository->countByPhase(Tournament::PHASE_REGISTRATION),
                'swiss' => $this->tournamentRepository->countByPhase(Tournament::PHASE_SWISS),
                'top_cut' => $this->tournamentRepository->countByPhase(Tournament::PHASE_TOP_CUT),
                'finished' => $this->tournamentRepository->countByPhase(Tournament::PHASE_FINISHED)
            ],
            'by_format' => [
                'swiss' => $this->tournamentRepository->countByTournamentFormat(Tournament::FORMAT_SWISS),
                'elimination' => $this->tournamentRepository->countByTournamentFormat(Tournament::FORMAT_ELIMINATION)
            ]
        ];
    }

    private function getRegistrationsStats(): array
    {
        return [
            'total' => $this->registrationRepository->countTotal(),
            'by_status' => [
                'active' => $this->registrationRepository->countActive(),
                'cancelled' => $this->registrationRepository->countCancelled(),
                'confirmed' => $this->registrationRepository->countConfirmed()
            ]
        ];
    }

    private function getUsersStats(): array
    {
        return [
            'total' => $this->userRepository->countAll(),
            'organizers' => $this->userRepository->countEventOrganizers(),
            'shops' => $this->shopRepository->countActive()
        ];
    }

    private function getEventStatsByPeriod(\DateTimeImmutable $startDate): array
    {
        return [
            'created' => $this->eventRepository->countCreatedSince($startDate),
            'approved' => $this->eventRepository->countApprovedSince($startDate),
            'rejected' => $this->eventRepository->countRejectedSince($startDate)
        ];
    }

    private function getEventStatsByType(\DateTimeImmutable $startDate): array
    {
        return [
            Event::TYPE_TOURNOI => $this->eventRepository->countByTypeAndPeriod(Event::TYPE_TOURNOI, $startDate),
            Event::TYPE_AVANT_PREMIERE => $this->eventRepository->countByTypeAndPeriod(Event::TYPE_AVANT_PREMIERE, $startDate),
            Event::TYPE_RENCONTRE => $this->eventRepository->countByTypeAndPeriod(Event::TYPE_RENCONTRE, $startDate),
            Event::TYPE_GENERIQUE => $this->eventRepository->countByTypeAndPeriod(Event::TYPE_GENERIQUE, $startDate)
        ];
    }

    private function getEventStatsByOrganizer(\DateTimeImmutable $startDate): array
    {
        return [
            'users' => $this->eventRepository->countByOrganizerTypeAndPeriod(Event::ORGANIZER_USER, $startDate),
            'shops' => $this->eventRepository->countByOrganizerTypeAndPeriod(Event::ORGANIZER_SHOP, $startDate)
        ];
    }

    private function getRegistrationStatsByPeriod(\DateTimeImmutable $startDate): array
    {
        return [
            'total' => $this->registrationRepository->countSince($startDate),
            'confirmed' => $this->registrationRepository->countConfirmedSince($startDate),
            'cancelled' => $this->registrationRepository->countCancelledSince($startDate)
        ];
    }

    private function getRegistrationStatsByEventType(\DateTimeImmutable $startDate): array
    {
        return [
            Event::TYPE_TOURNOI => $this->registrationRepository->countByEventTypeAndPeriod(Event::TYPE_TOURNOI, $startDate),
            Event::TYPE_AVANT_PREMIERE => $this->registrationRepository->countByEventTypeAndPeriod(Event::TYPE_AVANT_PREMIERE, $startDate),
            Event::TYPE_RENCONTRE => $this->registrationRepository->countByEventTypeAndPeriod(Event::TYPE_RENCONTRE, $startDate),
            Event::TYPE_GENERIQUE => $this->registrationRepository->countByEventTypeAndPeriod(Event::TYPE_GENERIQUE, $startDate)
        ];
    }

    private function getTournamentDetailedStats(\DateTimeImmutable $startDate): array
    {
        return [
            'total' => $this->tournamentRepository->countCreatedSince($startDate),
            'finished' => $this->tournamentRepository->countFinishedSince($startDate),
            'average_participants' => $this->tournamentRepository->getAverageParticipants($startDate),
            'by_game_format' => $this->tournamentRepository->getStatsByGameFormat($startDate)
        ];
    }

    private function getAdminAlerts(): array
    {
        $alerts = [];

        // Événements en attente depuis trop longtemps
        $oldPendingEvents = $this->eventRepository->findOldPendingReview(7); // 7 jours
        if (!empty($oldPendingEvents)) {
            $alerts[] = [
                'type' => 'old_pending_events',
                'priority' => 'medium',
                'message' => count($oldPendingEvents) . ' événement(s) en attente depuis plus de 7 jours',
                'count' => count($oldPendingEvents)
            ];
        }

        // Tournois qui commencent bientôt sans validation
        $upcomingUnvalidated = $this->eventRepository->findUpcomingUnvalidated(24); // 24h
        if (!empty($upcomingUnvalidated)) {
            $alerts[] = [
                'type' => 'upcoming_unvalidated',
                'priority' => 'high',
                'message' => count($upcomingUnvalidated) . ' événement(s) commencent dans 24h sans validation',
                'count' => count($upcomingUnvalidated)
            ];
        }

        // Organisateurs très actifs (possible spam)
        $suspiciousOrganizers = $this->userRepository->findSuspiciousOrganizers(10, 7); // 10 events en 7 jours
        if (!empty($suspiciousOrganizers)) {
            $alerts[] = [
                'type' => 'suspicious_organizers',
                'priority' => 'medium',
                'message' => count($suspiciousOrganizers) . ' organisateur(s) très actif(s) récemment',
                'count' => count($suspiciousOrganizers)
            ];
        }

        return $alerts;
    }

    private function buildAdminFilters(Request $request): array
    {
        $filters = ['include_hidden' => true]; // Admin voit tout

        // Filtres standard
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }

        if ($request->query->has('visibility')) {
            $filters['visibility'] = $request->query->get('visibility');
        }

        if ($request->query->has('event_type')) {
            $filters['event_type'] = $request->query->get('event_type');
        }

        if ($request->query->has('organizer_type')) {
            $filters['organizer_type'] = $request->query->get('organizer_type');
        }

        // Filtres admin spécifiques
        if ($request->query->has('reviewed_by')) {
            $filters['reviewed_by'] = $request->query->get('reviewed_by');
        }

        if ($request->query->has('created_since')) {
            try {
                $filters['created_since'] = new \DateTimeImmutable($request->query->get('created_since'));
            } catch (\Exception $e) {
                // Ignorer si date invalide
            }
        }

        return $filters;
    }

    private function notifyEventCreator(Event $event, string $action, ?string $message): void
    {
        // TODO: Implémenter système de notification
        // Pour l'instant, juste un log
        error_log("Event {$event->getId()} {$action} for user {$event->getCreatedBy()->getId()}: {$message}");
    }

    private function notifyEventCancellation(Event $event, string $reason): void
    {
        // TODO: Implémenter notification de masse aux participants
        // Pour l'instant, juste un log
        error_log("Event {$event->getId()} cancelled: {$reason}");
    }

    private function serializeEventForAdmin(Event $event): array
    {
        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'event_type' => $event->getEventType(),
            'status' => $event->getStatus(),
            'visibility' => $event->getVisibility(),
            'start_date' => $event->getStartDate()?->format('c'),
            'current_participants' => $event->getCurrentParticipants(),
            'max_participants' => $event->getMaxParticipants(),
            'created_at' => $event->getCreatedAt()?->format('c'),
            'created_by' => [
                'pseudo' => $event->getCreatedBy()->getPseudo(),
                'email' => $event->getCreatedBy()->getEmail()
            ],
            'organizer' => [
                'type' => $event->getOrganizerType(),
                'name' => $event->getOrganizerName()
            ],
            'admin_info' => [
                'reviewed_at' => $event->getReviewedAt()?->format('c'),
                'reviewed_by' => $event->getReviewedBy()?->getPseudo(),
                'review_comment' => $event->getReviewComment()
            ]
        ];

        // Données spécifiques aux tournois
        if ($event instanceof Tournament) {
            $data['tournament'] = [
                'game_format' => $event->getGameFormat()?->getFullName(),
                'tournament_format' => $event->getTournamentFormat(),
                'current_phase' => $event->getCurrentPhase(),
                'require_decklist' => $event->isRequireDecklist(),
                'prize_pool' => $event->getPrizePool()
            ];
        }

        return $data;
    }

    private function serializeEventForReview(Event $event): array
    {
        $data = $this->serializeEventForAdmin($event);
        
        // Ajouter informations utiles pour la review
        $data['description'] = $event->getDescription();
        $data['rules'] = $event->getRules();
        $data['registration_deadline'] = $event->getRegistrationDeadline()?->format('c');
        $data['is_online'] = $event->isOnline();
        
        // Jeux associés
        $data['games'] = [];
        foreach ($event->getGames() as $game) {
            $data['games'][] = [
                'id' => $game->getId(),
                'name' => $game->getName()
            ];
        }

        // Adresse si présente
        if ($event->getAddress()) {
            $address = $event->getAddress();
            $data['address'] = [
                'street_address' => $address->getStreetAddress(),
                'city' => $address->getCity(),
                'postal_code' => $address->getPostalCode(),
                'country' => $address->getCountry()
            ];
        }

        // Actions possibles
        $data['actions'] = [
            'can_approve' => $event->isPendingReview(),
            'can_reject' => $event->isPendingReview(),
            'can_hide' => $event->isVisible(),
            'can_show' => $event->isHidden(),
            'can_cancel' => !$event->isFinished() && !$event->isCancelled()
        ];

        return $data;
    }
}