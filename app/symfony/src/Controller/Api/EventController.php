<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Tournament;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\AddressRepository;
use App\Repository\ShopRepository;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Liste tous les événements visibles (publics)
     * GET /api/events
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Filtres depuis query params
        $filters = $this->buildFilters($request);
        
        // Seuls les événements visibles pour les users normaux
        if (!$this->isGranted('ROLE_ADMIN')) {
            $filters['include_hidden'] = false;
        }

        $events = $this->eventRepository->findWithFilters($filters, $limit, $offset);
        $total = $this->eventRepository->countWithFilters($filters);

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], $events),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    /**
     * Mes événements (créés par l'utilisateur connecté)
     * GET /api/events/my-events
     */
    #[Route('/my-events', name: 'my_events', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myEvents(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $createdEvents = $this->eventRepository->findByCreator($user);

        // Si l'user a une boutique, récupérer aussi les événements de sa boutique
        $shopEvents = [];
        if ($user->hasShop() && $user->canActAsShop()) {
            $shopEvents = $this->eventRepository->findByOrganizer(
                Event::ORGANIZER_SHOP,
                $user->getShop()->getId()
            );
        }

        return $this->json([
            'created_events' => array_map([$this, 'serializeEvent'], $createdEvents),
            'shop_events' => array_map([$this, 'serializeEvent'], $shopEvents)
        ]);
    }
    /**
     * Détails d'un événement
     * GET /api/events/{id}
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        // Vérifier visibilité (sauf admin)
        if (!$this->isGranted('ROLE_ADMIN') && !$event->isVisible()) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        return $this->json([
            'event' => $this->serializeEventDetailed($event)
        ]);
    }

    /**
     * Créer un nouvel événement
     * POST /api/events
     * MODIFICATION: Seuls organisateurs, boutiques et admins peuvent créer
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]

    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier permissions création (plus restrictif maintenant)
        if (!$this->canCreateEvent($user, $data)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        try {
            // Créer événement ou tournoi selon le type
            $event = $this->createEventFromData($data, $user);

            // Validation
            $errors = $this->validator->validate($event);
            if (count($errors) > 0) {
                return $this->json(['errors' => $this->formatValidationErrors($errors)], 400);
            }

            $this->em->persist($event);
            $this->em->flush();

            return $this->json([
                'message' => 'Événement créé avec succès',
                'event' => $this->serializeEventDetailed($event)
            ], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Modifier un événement
     * PUT /api/events/{id}
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier permissions modification
        if (!$this->canEditEvent($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides'], 400);
        }

        try {
            $this->updateEventFromData($event, $data);

            // Validation
            $errors = $this->validator->validate($event);
            if (count($errors) > 0) {
                return $this->json(['errors' => $this->formatValidationErrors($errors)], 400);
            }

            $this->em->flush();

            return $this->json([
                'message' => 'Événement modifié avec succès',
                'event' => $this->serializeEventDetailed($event)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la modification: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un événement
     * DELETE /api/events/{id}
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier permissions suppression
        if (!$this->canDeleteEvent($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        // Vérifier si l'événement peut être supprimé
        if (!$this->canEventBeDeleted($event)) {
            return $this->json(['error' => 'Cet événement ne peut pas être supprimé (inscriptions en cours ou événement démarré)'], 400);
        }

        try {
            $this->em->remove($event);
            $this->em->flush();

            return $this->json(['message' => 'Événement supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soumettre un événement pour validation admin
     * POST /api/events/{id}/submit-for-review
     */
    #[Route('/{id}/submit-for-review', name: 'submit_review', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitForReview(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canEditEvent($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        if ($event->getStatus() !== Event::STATUS_DRAFT) {
            return $this->json(['error' => 'Seuls les événements en brouillon peuvent être soumis'], 400);
        }

        try {
            $event->submitForReview();
            $this->em->flush();

            // TODO: Envoyer notification aux admins

            return $this->json([
                'message' => 'Événement soumis pour validation',
                'event' => $this->serializeEventDetailed($event)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la soumission: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Recherche d'événements
     * GET /api/events/search
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        if (strlen($query) < 2) {
            return $this->json(['error' => 'La recherche doit contenir au moins 2 caractères'], 400);
        }

        $onlyVisible = !$this->isGranted('ROLE_ADMIN');
        $events = $this->eventRepository->searchByText($query, $onlyVisible);

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], array_slice($events, 0, $limit)),
            'total' => count($events)
        ]);
    }

    /**
     * Événements populaires
     * GET /api/events/popular
     */
    #[Route('/popular', name: 'popular', methods: ['GET'])]
    public function popular(Request $request): JsonResponse
    {
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));
        $events = $this->eventRepository->findPopular($limit);

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], $events)
        ]);
    }

    /**
     * Événements à venir
     * GET /api/events/upcoming
     */
    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    public function upcoming(Request $request): JsonResponse
    {
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));
        $events = $this->eventRepository->findUpcoming($limit);

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], $events)
        ]);
    }

    /**
     * Événements par jeu
     * GET /api/events/by-game/{gameId}
     */
    #[Route('/by-game/{gameId}', name: 'by_game', methods: ['GET'])]
    public function byGame(int $gameId, GameRepository $gameRepository): JsonResponse
    {
        $game = $gameRepository->find($gameId);

        if (!$game) {
            return $this->json(['error' => 'Jeu non trouvé'], 404);
        }

        $onlyVisible = !$this->isGranted('ROLE_ADMIN');
        $events = $this->eventRepository->findByGame($game, $onlyVisible);

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], $events),
            'game' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug()
            ]
        ]);
    }

/**
 * Upload image pour un événement
 * POST /api/events/{id}/image
 */
#[Route('/{id}/image', name: 'upload_image', methods: ['POST', 'OPTIONS'])]
public function uploadImage(int $id, Request $request, FileUploadService $fileUploadService): JsonResponse
{
    // Gérer preflight OPTIONS pour CORS
    if ($request->getMethod() === 'OPTIONS') {
        return new JsonResponse(null, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
        ]);
    }

    // Headers CORS pour toutes les réponses
    $corsHeaders = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
    ];

    // DEBUG COMPLET DE LA REQUÊTE
    $this->logger->info('🔍 DEBUG - Request method: ' . $request->getMethod());
    $this->logger->info('🔍 DEBUG - Content type: ' . $request->headers->get('Content-Type'));
    $this->logger->info('🔍 DEBUG - All headers: ' . json_encode($request->headers->all()));
    $this->logger->info('🔍 DEBUG - POST data keys: ' . json_encode(array_keys($request->request->all())));
    $this->logger->info('🔍 DEBUG - Files keys: ' . json_encode($request->files->keys()));
    $this->logger->info('🔍 DEBUG - Raw content length: ' . strlen($request->getContent()));

    // Vérification authentification
    if (!$this->isGranted('ROLE_USER')) {
        return $this->json(['error' => 'Authentification requise'], 401, $corsHeaders);
    }

    $event = $this->eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Événement non trouvé'], 404, $corsHeaders);
    }

    if (!$this->canEditEvent($event, $this->getUser())) {
        return $this->json(['error' => 'Permissions insuffisantes'], 403, $corsHeaders);
    }
$this->logger->info('🔍 Raw Content-Type: ' . $_SERVER['CONTENT_TYPE']);
$this->logger->info('🔍 php://input size: ' . strlen(file_get_contents('php://input')));
    $file = $request->files->get('image');
    
    if (!$file) {
        return $this->json(['error' => 'Aucun fichier fourni'], 400, $corsHeaders);
    }

    try {
        // Supprimer ancienne image si existe
        if ($event->getImage()) {
            $fileUploadService->deleteFile($event->getImage());
        }

        // Upload nouvelle image
        $filename = $fileUploadService->uploadEventImage($file, $event->getId());
        $event->setImage($filename);
        
        $this->em->flush();

        return $this->json([
            'message' => 'Image uploadée avec succès',
            'imageUrl' => $fileUploadService->getEventImageUrl($filename)
        ], 200, $corsHeaders);

    } catch (\Exception $e) {
        return $this->json(['error' => 'Erreur upload: ' . $e->getMessage()], 500, $corsHeaders);
    }
}

    // ============= MÉTHODES PRIVÉES =============

    private function buildFilters(Request $request): array
    {
        $filters = [];

        // Filtres de base
        if ($request->query->has('event_type')) {
            $filters['event_type'] = $request->query->get('event_type');
        }

        if ($request->query->has('game_id')) {
            $filters['game_id'] = (int) $request->query->get('game_id');
        }

        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }

        if ($request->query->has('is_online')) {
            $filters['is_online'] = filter_var($request->query->get('is_online'), FILTER_VALIDATE_BOOLEAN);
        }

        // Filtres de dates
        if ($request->query->has('start_date')) {
            try {
                $filters['start_date'] = new \DateTimeImmutable($request->query->get('start_date'));
            } catch (\Exception $e) {
                // Ignorer si date invalide
            }
        }

        if ($request->query->has('end_date')) {
            try {
                $filters['end_date'] = new \DateTimeImmutable($request->query->get('end_date'));
            } catch (\Exception $e) {
                // Ignorer si date invalide
            }
        }

        // Tri
        if ($request->query->has('order_by')) {
            $filters['order_by'] = $request->query->get('order_by');
        }

        if ($request->query->has('order_direction')) {
            $orderDirection = strtoupper($request->query->get('order_direction'));
            if (in_array($orderDirection, ['ASC', 'DESC'])) {
                $filters['order_direction'] = $orderDirection;
            }
        }

        return $filters;
    }

    private function canCreateEvent(User $user, array $data): bool
    {
        // NOUVELLE LOGIQUE: Seuls organisateurs, boutiques et admins peuvent créer des événements
        return $this->isGranted('ROLE_ORGANIZER') || 
               $this->isGranted('ROLE_SHOP') || 
               $this->isGranted('ROLE_ADMIN') || 
               $user->canActAsShop();
    }

    private function canEditEvent(Event $event, User $user): bool
    {
        // Admin peut tout modifier
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Créateur peut modifier ses événements
        if ($event->getCreatedBy() === $user) {
            return true;
        }

        // Propriétaire de boutique peut modifier les événements de sa boutique
        if ($event->getOrganizerType() === Event::ORGANIZER_SHOP && 
            $user->hasShop() && 
            $event->getOrganizerId() === $user->getShop()->getId()) {
            return true;
        }

        return false;
    }

    private function canDeleteEvent(Event $event, User $user): bool
    {
        // Mêmes règles que l'édition
        return $this->canEditEvent($event, $user);
    }

    private function canEventBeDeleted(Event $event): bool
    {
        // Ne pas supprimer si événement démarré
        if (in_array($event->getStatus(), [Event::STATUS_IN_PROGRESS, Event::STATUS_FINISHED])) {
            return false;
        }

        // Ne pas supprimer si des inscriptions existent
        if ($event->getCurrentParticipants() > 0) {
            return false;
        }

        return true;
    }

    private function createEventFromData(array $data, User $user): Event
    {
        $eventType = $data['event_type'] ?? Event::TYPE_GENERIQUE;

        // Créer Tournament ou Event selon le type
        if ($eventType === Event::TYPE_TOURNOI) {
            $event = new Tournament();
        } else {
            $event = new Event();
            $event->setEventType($eventType);
        }

        // Données communes
        $this->updateEventFromData($event, $data);

        // Définir le créateur et organisateur
        $event->setCreatedBy($user);
        
        // Déterminer le type d'organisateur
        if (isset($data['organizer_type']) && $data['organizer_type'] === Event::ORGANIZER_SHOP) {
            if ($user->canActAsShop()) {
                $event->setOrganizerType(Event::ORGANIZER_SHOP);
                $event->setOrganizerId($user->getShop()->getId());
            } else {
                throw new \InvalidArgumentException('Utilisateur ne peut pas organiser en tant que boutique');
            }
        } else {
            $event->setOrganizerType(Event::ORGANIZER_USER);
            $event->setOrganizerId($user->getId());
        }

        return $event;
    }

    private function updateEventFromData(Event $event, array $data): void
    {
        // Champs obligatoires
        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        // Dates
        if (isset($data['start_date'])) {
            $event->setStartDate(new \DateTimeImmutable($data['start_date']));
        }

        if (isset($data['end_date'])) {
            $event->setEndDate(new \DateTimeImmutable($data['end_date']));
        }

        if (isset($data['registration_deadline'])) {
            $event->setRegistrationDeadline(new \DateTimeImmutable($data['registration_deadline']));
        }

        // Participants
        if (isset($data['max_participants'])) {
            $event->setMaxParticipants($data['max_participants']);
        }

        // Lieu
        if (isset($data['is_online'])) {
            $event->setIsOnline($data['is_online']);
        }

        // TODO: Gérer address_id si fourni

        // Contenu
        if (isset($data['tags'])) {
            $event->setTags($data['tags']);
        }

        if (isset($data['rules'])) {
            $event->setRules($data['rules']);
        }

        if (isset($data['prizes'])) {
            $event->setPrizes($data['prizes']);
        }

        if (isset($data['stream_url'])) {
            $event->setStreamUrl($data['stream_url']);
        }

        // Jeux associés
        if (isset($data['game_ids']) && is_array($data['game_ids'])) {
            $gameRepository = $this->em->getRepository(\App\Entity\Game::class);
            
            // Vider les jeux existants
            foreach ($event->getGames() as $game) {
                $event->removeGame($game);
            }
            
            // Ajouter les nouveaux jeux
            foreach ($data['game_ids'] as $gameId) {
                $game = $gameRepository->find($gameId);
                if ($game) {
                    $event->addGame($game);
                }
            }
        }

        // Données spécifiques aux tournois
        if ($event instanceof Tournament) {
            $this->updateTournamentFromData($event, $data);
        }
    }

    private function updateTournamentFromData(Tournament $tournament, array $data): void
    {
        // Game format obligatoire pour les tournois
        if (isset($data['game_format_id'])) {
            $gameFormatRepository = $this->em->getRepository(\App\Entity\GameFormat::class);
            $gameFormat = $gameFormatRepository->find($data['game_format_id']);
            if ($gameFormat) {
                $tournament->setGameFormat($gameFormat);
            }
        }

        // Format de tournoi
        if (isset($data['tournament_format'])) {
            $tournament->setTournamentFormat($data['tournament_format']);
        }

        // Configuration
        if (isset($data['match_timer'])) {
            $tournament->setMatchTimer($data['match_timer']);
        }

        if (isset($data['break_timer'])) {
            $tournament->setBreakTimer($data['break_timer']);
        }

        if (isset($data['swiss_rounds'])) {
            $tournament->setSwissRounds($data['swiss_rounds']);
        }

        if (isset($data['top_cut_size'])) {
            $tournament->setTopCutSize($data['top_cut_size']);
        }

        // Decklists
        if (isset($data['allow_decklist'])) {
            $tournament->setAllowDecklist($data['allow_decklist']);
        }

        if (isset($data['require_decklist'])) {
            $tournament->setRequireDecklist($data['require_decklist']);
        }

        // Prize pool
        if (isset($data['prize_pool'])) {
            $tournament->setPrizePool($data['prize_pool']);
        }
    }

    private function serializeEvent(Event $event): array
    {
        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'event_type' => $event->getEventType(),
            'status' => $event->getStatus(),
            'visibility' => $event->getVisibility(),
            'start_date' => $event->getStartDate()?->format('c'),
            'end_date' => $event->getEndDate()?->format('c'),
            'registration_deadline' => $event->getRegistrationDeadline()?->format('c'),
            'max_participants' => $event->getMaxParticipants(),
            'current_participants' => $event->getCurrentParticipants(),
            'is_online' => $event->isOnline(),
            'organizer_type' => $event->getOrganizerType(),
            'organizer_name' => $event->getOrganizerName(),
            'tags' => $event->getTags(),
            'image' => $event->getImage(),
            'stream_url' => $event->getStreamUrl(),
            'created_at' => $event->getCreatedAt()?->format('c'),
            'can_register' => $event->canRegister(),
            'is_full' => $event->isFull(),
            'remaining_slots' => $event->getRemainingSlots()
        ];

        // Données spécifiques aux tournois
        if ($event instanceof Tournament) {
            $data['tournament'] = [
                'game_format_id' => $event->getGameFormat()?->getId(),
                'game_format_name' => $event->getGameFormat()?->getFullName(),
                'tournament_format' => $event->getTournamentFormat(),
                'current_phase' => $event->getCurrentPhase(),
                'current_round' => $event->getCurrentRound(),
                'swiss_rounds' => $event->getSwissRounds(),
                'top_cut_size' => $event->getTopCutSize(),
                'match_timer' => $event->getMatchTimer(),
                'allow_decklist' => $event->isAllowDecklist(),
                'require_decklist' => $event->isRequireDecklist(),
                'prize_pool' => $event->getPrizePool(),
                'progress' => $event->getProgress()
            ];
        }

        return $data;
    }

    private function serializeEventDetailed(Event $event): array
    {
        $data = $this->serializeEvent($event);

        // Ajouter détails supplémentaires
        $data['rules'] = $event->getRules();
        $data['prizes'] = $event->getPrizes();
        $data['updated_at'] = $event->getUpdatedAt()?->format('c');

        // Jeux associés
        $data['games'] = [];
        foreach ($event->getGames() as $game) {
            $data['games'][] = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug(),
                'logo' => $game->getLogo()
            ];
        }

        // Adresse si présente
        if ($event->getAddress()) {
            $address = $event->getAddress();
            $data['address'] = [
                'id' => $address->getId(),
                'street_address' => $address->getStreetAddress(),
                'city' => $address->getCity(),
                'postal_code' => $address->getPostalCode(),
                'country' => $address->getCountry(),
                'full_address' => $address->getFullAddress()
            ];
        }

        // Infos de review pour les admins
        if ($this->isGranted('ROLE_ADMIN')) {
            $data['admin_info'] = [
                'reviewed_at' => $event->getReviewedAt()?->format('c'),
                'reviewed_by' => $event->getReviewedBy()?->getPseudo(),
                'review_comment' => $event->getReviewComment()
            ];
        }

        return $data;
    }

    private function formatValidationErrors($errors): array
    {
        $formattedErrors = [];
        foreach ($errors as $error) {
            $formattedErrors[] = [
                'field' => $error->getPropertyPath(),
                'message' => $error->getMessage()
            ];
        }
        return $formattedErrors;
    }
}