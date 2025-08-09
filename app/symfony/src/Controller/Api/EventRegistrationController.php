<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_registration_')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private EventRegistrationRepository $registrationRepository,
        private ValidatorInterface $validator
    ) {}

/**
 * S'inscrire à un événement
 * POST /api/events/{id}/register
 */
#[Route('/events/{id}/register', name: 'register', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function register(int $id, Request $request): JsonResponse
{
    $event = $this->eventRepository->find($id);

    if (!$event) {
        return $this->json(['error' => 'Événement non trouvé'], 404);
    }

    /** @var User $user */
    $user = $this->getUser();

    // Vérifier si inscription possible
    if (!$event->canRegister()) {
        return $this->json(['error' => 'Les inscriptions ne sont pas ouvertes pour cet événement'], 400);
    }

    // CORRECTION : Vérifier s'il existe une inscription active (pas annulée)
    $existingActiveRegistration = $this->registrationRepository->findActiveUserRegistration($user, $event);
    
    if ($existingActiveRegistration) {
        return $this->json(['error' => 'Vous êtes déjà inscrit à cet événement'], 400);
    }

    $data = json_decode($request->getContent(), true) ?? [];

    try {
        // CORRECTION : Chercher une inscription annulée existante pour la réactiver
        $existingCancelledRegistration = $this->registrationRepository->findCancelledUserRegistration($user, $event);
        
        if ($existingCancelledRegistration) {
            // Réactiver l'inscription existante
            $registration = $existingCancelledRegistration;
            $registration->setStatus(EventRegistration::STATUS_REGISTERED);
            $registration->setRegisteredAt(new \DateTimeImmutable());
            $registration->setConfirmedAt(null);
            $registration->setCancelledAt(null);
            
            // Réinitialiser les données de tournoi si nécessaire
            $registration->setCheckedIn(false);
            $registration->setCheckedInAt(null);
            
        } else {
            // Créer une nouvelle inscription
            $registration = new EventRegistration();
            $registration->setEvent($event);
            $registration->setUser($user);
            $this->em->persist($registration);
        }

        // Données optionnelles (communes aux deux cas)
        if (isset($data['notes'])) {
            $registration->setNotes($data['notes']);
        }

        // Pour les tournois, gérer la decklist si fournie
        if ($event instanceof Tournament && isset($data['deck_list'])) {
            if ($event->isAllowDecklist()) {
                $registration->submitDeckList($data['deck_list']);
            }
        }

        // Gérer deck_id si fourni
        if (isset($data['deck_id']) && $event instanceof Tournament && $event->isAllowDecklist()) {
            $deckRepository = $this->em->getRepository(\App\Entity\Deck::class);
            $deck = $deckRepository->find($data['deck_id']);
            
            if ($deck && $deck->getUser() === $user) {
                $registration->setDeck($deck);
            }
        }

        // Validation
        $errors = $this->validator->validate($registration);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatValidationErrors($errors)], 400);
        }
        
        // Mettre à jour le compteur de participants
        $event->setCurrentParticipants($event->getCurrentParticipants() + 1);
        
        $this->em->flush();

        return $this->json([
            'message' => 'Inscription réussie',
            'registration' => $this->serializeRegistration($registration)
        ], 201);

    } catch (\Exception $e) {
        return $this->json(['error' => 'Erreur lors de l\'inscription: ' . $e->getMessage()], 500);
    }
}

    /**
     * Se désinscrire d'un événement
     * DELETE /api/events/{id}/register
     */
    #[Route('/events/{id}/register', name: 'unregister', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function unregister(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        $registration = $this->registrationRepository->findUserRegistration($user, $event);

        if (!$registration) {
            return $this->json(['error' => 'Vous n\'êtes pas inscrit à cet événement'], 400);
        }

        // Vérifier si désinscription possible
        if (!$this->canUnregister($registration)) {
            return $this->json(['error' => 'Vous ne pouvez plus vous désinscrire de cet événement'], 400);
        }

        try {
            $registration->cancel();
            
            // Mettre à jour le compteur de participants
            $event->setCurrentParticipants(max(0, $event->getCurrentParticipants() - 1));
            
            $this->em->flush();

            return $this->json(['message' => 'Désinscription réussie']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la désinscription: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtenir mes prochaines participations
     * GET /api/my-upcoming-events
     */
    #[Route('/my-upcoming-events', name: 'upcoming_events', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function upcomingEvents(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $registrations = $this->registrationRepository->findUpcomingForUser($user);

        return $this->json([
            'upcoming_events' => array_map([$this, 'serializeRegistrationWithEvent'], $registrations)
        ]);
    }

    /**
 * Vérifier le statut d'inscription d'un utilisateur à un événement
 * GET /api/events/{id}/registration-status
 */
#[Route('/events/{id}/registration-status', name: 'registration_status', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function registrationStatus(int $id): JsonResponse
{
    $event = $this->eventRepository->find($id);

    if (!$event) {
        return $this->json(['error' => 'Événement non trouvé'], 404);
    }

    /** @var User $user */
    $user = $this->getUser();

    $registration = $this->registrationRepository->findUserRegistration($user, $event);

    if (!$registration) {
        return $this->json([
            'is_registered' => false,
            'can_register' => $event->canRegister(),
            'registration' => null
        ]);
    }

    return $this->json([
        'is_registered' => $registration->isActive(),
        'can_register' => false,
        'registration' => $this->serializeRegistration($registration)
    ]);
}

/**
 * Obtenir mes inscriptions
 * GET /api/my-registrations
 */
#[Route('/my-registrations', name: 'my_registrations', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function myRegistrations(Request $request): JsonResponse
{
    /** @var User $user */
    $user = $this->getUser();

    $status = $request->query->get('status');
    
    if ($status) {
        // Gérer les statuts multiples séparés par des virgules
        $statusArray = explode(',', $status);
        $statusArray = array_map('trim', $statusArray);
        
        // Filtrer par statuts multiples
        $registrations = $this->registrationRepository->findWithFilters([
            'user_id' => $user->getId(),
            'status_in' => $statusArray  // Nouveau filtre pour statuts multiples
        ]);
    } else {
        $registrations = $this->registrationRepository->findByUser($user);
    }

    return $this->json([
        'registrations' => array_map([$this, 'serializeRegistrationWithEvent'], $registrations)
    ]);
}

    /**
     * Soumettre une decklist pour un tournoi
     * POST /api/events/{id}/submit-decklist
     */
    #[Route('/events/{id}/submit-decklist', name: 'submit_decklist', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitDecklist(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event || !$event instanceof Tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        $registration = $this->registrationRepository->findUserRegistration($user, $event);

        if (!$registration) {
            return $this->json(['error' => 'Vous n\'êtes pas inscrit à ce tournoi'], 400);
        }

        if (!$registration->canSubmitDeckList()) {
            return $this->json(['error' => 'Vous ne pouvez pas soumettre de decklist pour ce tournoi'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['deck_list']) && !isset($data['deck_id'])) {
            return $this->json(['error' => 'Decklist ou deck_id requis'], 400);
        }

        try {
            // Soumission par texte
            if (isset($data['deck_list'])) {
                $registration->submitDeckList($data['deck_list']);
            }

            // Soumission par deck existant
            if (isset($data['deck_id'])) {
                $deckRepository = $this->em->getRepository(\App\Entity\Deck::class);
                $deck = $deckRepository->find($data['deck_id']);
                
                if (!$deck || $deck->getUser() !== $user) {
                    return $this->json(['error' => 'Deck non trouvé ou non autorisé'], 400);
                }

                $registration->setDeck($deck);
                if (!$registration->isDeckListSubmitted()) {
                    $registration->setDeckListSubmitted(true);
                }
            }

            $this->em->flush();

            return $this->json([
                'message' => 'Decklist soumise avec succès',
                'registration' => $this->serializeRegistration($registration)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la soumission: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check-in pour un événement (organisateur ou admin)
     * POST /api/events/{id}/check-in/{userId}
     */
    #[Route('/events/{id}/check-in/{userId}', name: 'check_in_user', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkInUser(int $id, int $userId): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier permissions organisateur
        if (!$this->canManageEvent($event, $currentUser)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $userRepository = $this->em->getRepository(User::class);
        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $registration = $this->registrationRepository->findUserRegistration($targetUser, $event);

        if (!$registration) {
            return $this->json(['error' => 'Utilisateur non inscrit à cet événement'], 400);
        }

        if (!$registration->canCheckIn()) {
            return $this->json(['error' => 'Check-in impossible pour cette inscription'], 400);
        }

        try {
            $registration->checkIn();
            $this->em->flush();

            return $this->json([
                'message' => 'Check-in effectué avec succès',
                'registration' => $this->serializeRegistration($registration)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du check-in: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Auto check-in (utilisateur se check-in lui-même)
     * POST /api/events/{id}/self-check-in
     */
    #[Route('/events/{id}/self-check-in', name: 'self_check_in', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function selfCheckIn(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        $registration = $this->registrationRepository->findUserRegistration($user, $event);

        if (!$registration) {
            return $this->json(['error' => 'Vous n\'êtes pas inscrit à cet événement'], 400);
        }

        if (!$registration->canCheckIn()) {
            return $this->json(['error' => 'Check-in impossible'], 400);
        }

        // Vérifier si auto check-in autorisé (ex: proche de l'heure de début)
        if (!$this->canSelfCheckIn($event)) {
            return $this->json(['error' => 'Auto check-in non disponible pour le moment'], 400);
        }

        try {
            $registration->checkIn();
            $this->em->flush();

            return $this->json([
                'message' => 'Check-in effectué avec succès',
                'registration' => $this->serializeRegistration($registration)
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du check-in: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Liste des participants d'un événement (organisateur)
     * GET /api/events/{id}/participants
     */
    #[Route('/events/{id}/participants', name: 'participants', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function participants(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier permissions
        if (!$this->canViewParticipants($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $status = $request->query->get('status');
        $checkedIn = $request->query->get('checked_in');

        $filters = ['event_id' => $id];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        if ($checkedIn !== null) {
            $filters['checked_in'] = filter_var($checkedIn, FILTER_VALIDATE_BOOLEAN);
        }

        $registrations = $this->registrationRepository->findWithFilters($filters);

        // Statistiques pour l'organisateur
        $stats = $this->registrationRepository->getRegistrationStats($event);

        return $this->json([
            'participants' => array_map([$this, 'serializeRegistrationForOrganizer'], $registrations),
            'stats' => $stats
        ]);
    }

    /**
     * Participants nécessitant un check-in
     * GET /api/events/{id}/pending-check-in
     */
    #[Route('/events/{id}/pending-check-in', name: 'pending_check_in', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function pendingCheckIn(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageEvent($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $pendingRegistrations = $this->registrationRepository->findPendingCheckIn($event);

        return $this->json([
            'pending_check_in' => array_map([$this, 'serializeRegistrationForOrganizer'], $pendingRegistrations),
            'count' => count($pendingRegistrations)
        ]);
    }

    /**
     * Statistiques des inscriptions d'un événement
     * GET /api/events/{id}/registration-stats
     */
    #[Route('/events/{id}/registration-stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function registrationStats(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canViewParticipants($event, $user)) {
            return $this->json(['error' => 'Permissions insuffisantes'], 403);
        }

        $stats = $this->registrationRepository->getRegistrationStats($event);

        // Ajout d'infos spécifiques aux tournois
        if ($event instanceof Tournament) {
            $decklistStats = [
                'with_decklist' => count($this->registrationRepository->findWithDeckList($event)),
                'missing_decklist' => count($this->registrationRepository->findMissingDeckList($event))
            ];
            $stats['decklist'] = $decklistStats;
        }

        return $this->json(['stats' => $stats]);
    }

    // ============= MÉTHODES PRIVÉES =============

    private function canUnregister(EventRegistration $registration): bool
    {
        $event = $registration->getEvent();

        // Ne pas permettre désinscription si événement démarré
        if ($event->isInProgress() || $event->isFinished()) {
            return false;
        }

        // Ne pas permettre si check-in effectué
        if ($registration->isCheckedIn()) {
            return false;
        }

        // Vérifier deadline désinscription si définie
        $deadline = $event->getRegistrationDeadline();
        if ($deadline && $deadline < new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    private function canManageEvent(Event $event, User $user): bool
    {
        // Admin peut tout gérer
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Créateur peut gérer
        if ($event->getCreatedBy() === $user) {
            return true;
        }

        // Propriétaire boutique peut gérer événements de sa boutique
        if ($event->getOrganizerType() === Event::ORGANIZER_SHOP && 
            $user->hasShop() && 
            $event->getOrganizerId() === $user->getShop()->getId()) {
            return true;
        }

        return false;
    }

    private function canViewParticipants(Event $event, User $user): bool
    {
        // Mêmes règles que canManageEvent
        return $this->canManageEvent($event, $user);
    }

    private function canSelfCheckIn(Event $event): bool
    {
        // Auto check-in possible 2h avant début jusqu'à 30min après début
        $now = new \DateTimeImmutable();
        $startDate = $event->getStartDate();
        
        if (!$startDate) {
            return false;
        }

        $checkInStart = $startDate->modify('-2 hours');
        $checkInEnd = $startDate->modify('+30 minutes');

        return $now >= $checkInStart && $now <= $checkInEnd;
    }

    private function serializeRegistration(EventRegistration $registration): array
    {
        return [
            'id' => $registration->getId(),
            'status' => $registration->getStatus(),
            'registered_at' => $registration->getRegisteredAt()?->format('c'),
            'confirmed_at' => $registration->getConfirmedAt()?->format('c'),
            'checked_in' => $registration->isCheckedIn(),
            'checked_in_at' => $registration->getCheckedInAt()?->format('c'),
            'deck_list_submitted' => $registration->isDeckListSubmitted(),
            'deck_list_submitted_at' => $registration->getDeckListSubmittedAt()?->format('c'),
            'seed_number' => $registration->getSeedNumber(),
            'final_ranking' => $registration->getFinalRanking(),
            'tournament_stats' => $registration->getTournamentStats(),
            'notes' => $registration->getNotes()
        ];
    }

    private function serializeRegistrationWithEvent(EventRegistration $registration): array
    {
        $data = $this->serializeRegistration($registration);
        $event = $registration->getEvent();

        $data['event'] = $this->serializeEventComplete($event);

        return $data;
    }

    /**
 * Sérialisation complète d'un événement (copie de EventController::serializeEvent)
 */
private function serializeEventComplete(Event $event): array
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
        'created_by_id' => $event->getCreatedBy()?->getId(),
        'created_by' => $event->getCreatedBy() ? $this->serializeEventCreator($event->getCreatedBy()) : null,
        'is_online' => $event->isOnline(),
        'organizer_type' => $event->getOrganizerType(),
        'organizer_name' => $event->getOrganizerName(),
        'tags' => $event->getTags(),
        'image' => $event->getImage(), // ← VOICI L'IMAGE !
        'stream_url' => $event->getStreamUrl(),
        'created_at' => $event->getCreatedAt()?->format('c'),
        'can_register' => $event->canRegister(),
        'is_full' => $event->isFull(),
        'remaining_slots' => $event->getRemainingSlots()
    ];

    // Ajouter les participants
    $participants = [];
    foreach ($event->getRegistrations() as $reg) {
        if ($reg->isActive()) {
            $participants[] = [
                'id' => $reg->getId(),
                'status' => $reg->getStatus(),
                'user' => [
                    'id' => $reg->getUser()->getId(),
                    'pseudo' => $reg->getUser()->getPseudo()
                ],
                'registered_at' => $reg->getRegisteredAt()?->format('c'),
                'checked_in' => $reg->isCheckedIn()
            ];
        }
    }
    $data['participants'] = $participants;

    // Ajouter les jeux
    $data['games'] = [];
    foreach ($event->getGames() as $game) {
        $data['games'][] = [
            'id' => $game->getId(),
            'name' => $game->getName(),
            'slug' => $game->getSlug(),
            'logo' => $game->getLogo()
        ];
    }

    // Ajouter l'adresse si présente
    if ($event->getAddress()) {
        $address = $event->getAddress();
        $data['address'] = [
            'id' => $address->getId(),
            'street_address' => $address->getStreetAddress(),
            'city' => $address->getCity(),
            'postal_code' => $address->getPostalCode(),
            'country' => $address->getCountry(),
            'full_address' => $address->getFullAddress(),
            'latitude' => $address->getLatitude(),
            'longitude' => $address->getLongitude()
        ];
    }

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

/**
 * Sérialisation du créateur d'événement
 */
private function serializeEventCreator(User $creator): array
{
    $data = [
        'id' => $creator->getId(),
        'pseudo' => $creator->getPseudo(),
        'avatar' => $creator->getAvatar(),
        'full_name' => $creator->getFullName(),
        'display_name' => $creator->getDisplayName(), 
        'entity_type' => $creator->getEntityType()    
    ];

    if ($creator->hasShop()) {
        $shop = $creator->getShop();
        $data['shop'] = [
            'id' => $shop->getId(),
            'name' => $shop->getName(),
            'logo' => $shop->getLogo(),
            'isActive' => $shop->isActive(),   
            'isVerified' => $shop->isVerified() 
        ];
    }

    return $data;
}

    private function serializeRegistrationForOrganizer(EventRegistration $registration): array
    {
        $data = $this->serializeRegistration($registration);
        
        // Ajouter infos utilisateur pour organisateur
        $user = $registration->getUser();
        $data['user'] = [
            'id' => $user->getId(),
            'pseudo' => $user->getPseudo(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName()
        ];

        // Ajouter infos deck si applicable
        if ($registration->getDeck()) {
            $deck = $registration->getDeck();
            $data['deck'] = [
                'id' => $deck->getId(),
                'title' => $deck->getTitle(),
                'format' => $deck->getGameFormat()?->getName()
            ];
        }

        // Masquer données sensibles sauf pour admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            unset($data['deck_list']);
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