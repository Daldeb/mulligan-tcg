<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\Tournament;
use App\Entity\RoleRequest;
use App\Repository\NotificationRepository;
use App\Repository\EventRepository;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private EventRepository $eventRepository,
        private EventRegistrationRepository $registrationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée une notification générique
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $icon = null,
        ?Event $relatedEvent = null,
        ?User $relatedUser = null,
        string $priority = 'normal',
        ?string $category = null
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setData($data)
            ->setActionUrl($actionUrl)
            ->setActionLabel($actionLabel)
            ->setIcon($icon)
            ->setRelatedEvent($relatedEvent)
            ->setRelatedUser($relatedUser)
            ->setPriority($priority)
            ->setCategory($category);

        // Auto-configuration si pas d'icône fournie
        if (!$icon) {
            $notification->autoConfigureAppearance();
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    // ============= MÉTHODES EXISTANTES (INCHANGÉES) =============

    /**
     * Crée une notification pour demande de rôle approuvée
     */
    public function createRoleApprovedNotification(RoleRequest $roleRequest): Notification
    {
        $user = $roleRequest->getUser();
        $roleName = $this->getRoleDisplayName($roleRequest->getRequestedRole());
        
        return $this->create(
            user: $user,
            type: Notification::TYPE_ROLE_APPROVED,
            title: 'Demande approuvée !',
            message: "Félicitations ! Votre demande de rôle {$roleName} a été approuvée.",
            data: [
                'role_request_id' => $roleRequest->getId(),
                'requested_role' => $roleRequest->getRequestedRole(),
                'approved_at' => $roleRequest->getReviewedAt()?->format('c'),
                'admin_id' => $roleRequest->getReviewedBy()?->getId()
            ],
            actionUrl: '/profile',
            actionLabel: 'Voir mon profil',
            icon: '🎉',
            category: 'admin',
            priority: 'high'
        );
    }

    /**
     * Crée une notification pour demande de rôle rejetée
     */
    public function createRoleRejectedNotification(RoleRequest $roleRequest): Notification
    {
        $user = $roleRequest->getUser();
        $roleName = $this->getRoleDisplayName($roleRequest->getRequestedRole());
        
        $message = "Votre demande de rôle {$roleName} a été refusée.";
        if ($roleRequest->getAdminResponse()) {
            $message .= " Raison : " . $roleRequest->getAdminResponse();
        }
        
        return $this->create(
            user: $user,
            type: Notification::TYPE_ROLE_REJECTED,
            title: 'Demande refusée',
            message: $message,
            data: [
                'role_request_id' => $roleRequest->getId(),
                'requested_role' => $roleRequest->getRequestedRole(),
                'rejected_at' => $roleRequest->getReviewedAt()?->format('c'),
                'admin_response' => $roleRequest->getAdminResponse(),
                'admin_id' => $roleRequest->getReviewedBy()?->getId()
            ],
            actionUrl: '/profile?tab=roles',
            actionLabel: 'Faire une nouvelle demande',
            icon: '❌',
            category: 'admin',
            priority: 'high'
        );
    }

    // ============= NOUVELLES MÉTHODES POUR ÉVÉNEMENTS =============

    /**
     * Notifie l'approbation d'un événement
     */
    public function createEventApprovedNotification(Event $event, User $admin, ?string $comment = null): Notification
    {
        $creator = $event->getCreatedBy();
        
        $message = sprintf(
            'Votre événement "%s" a été approuvé%s et est maintenant visible publiquement.',
            $event->getTitle(),
            $comment ? " avec le commentaire : {$comment}" : ''
        );

        return $this->create(
            user: $creator,
            type: Notification::TYPE_EVENT_APPROVED,
            title: 'Événement approuvé',
            message: $message,
            data: [
                'event_id' => $event->getId(),
                'admin_id' => $admin->getId(),
                'admin_comment' => $comment,
                'approved_at' => (new \DateTimeImmutable())->format('c')
            ],
            relatedEvent: $event,
            relatedUser: $admin,
            priority: 'high',
            category: 'admin'
        );
    }

    /**
     * Notifie le refus d'un événement
     */
    public function createEventRejectedNotification(Event $event, User $admin, string $reason): Notification
    {
        $creator = $event->getCreatedBy();
        
        $message = sprintf(
            'Votre événement "%s" a été refusé. Motif : %s. Vous pouvez le modifier et le re-soumettre.',
            $event->getTitle(),
            $reason
        );

        return $this->create(
            user: $creator,
            type: Notification::TYPE_EVENT_REJECTED,
            title: 'Événement refusé',
            message: $message,
            data: [
                'event_id' => $event->getId(),
                'admin_id' => $admin->getId(),
                'rejection_reason' => $reason,
                'rejected_at' => (new \DateTimeImmutable())->format('c')
            ],
            relatedEvent: $event,
            relatedUser: $admin,
            priority: 'high',
            category: 'admin'
        );
    }

    /**
     * Notifie la suppression d'un événement
     */
    public function createEventDeletedNotification(Event $event, User $admin, string $reason): Notification
    {
        $creator = $event->getCreatedBy();
        
        $message = sprintf(
            'Votre événement "%s" a été supprimé définitivement. Motif : %s',
            $event->getTitle(),
            $reason
        );

        return $this->create(
            user: $creator,
            type: Notification::TYPE_EVENT_DELETED,
            title: 'Événement supprimé',
            message: $message,
            data: [
                'event_title' => $event->getTitle(), // Pas d'event_id car supprimé
                'admin_id' => $admin->getId(),
                'deletion_reason' => $reason,
                'deleted_at' => (new \DateTimeImmutable())->format('c')
            ],
            actionUrl: '/mes-evenements',
            actionLabel: 'Voir mes événements',
            icon: 'pi-trash',
            relatedUser: $admin,
            priority: 'urgent',
            category: 'admin'
        );
    }

    /**
     * Notifie une nouvelle inscription à l'organisateur
     */
    public function createNewRegistrationNotification(EventRegistration $registration): Notification
    {
        $event = $registration->getEvent();
        $participant = $registration->getUser();
        $creator = $event->getCreatedBy();
        
        $message = sprintf(
            '%s s\'est inscrit(e) à votre événement "%s" (%d/%s participants)',
            $participant->getPseudo(),
            $event->getTitle(),
            $event->getCurrentParticipants(),
            $event->getMaxParticipants() ?? '∞'
        );

        return $this->create(
            user: $creator,
            type: Notification::TYPE_NEW_REGISTRATION,
            title: 'Nouvelle inscription',
            message: $message,
            data: [
                'event_id' => $event->getId(),
                'participant_id' => $participant->getId(),
                'participant_pseudo' => $participant->getPseudo(),
                'current_participants' => $event->getCurrentParticipants(),
                'max_participants' => $event->getMaxParticipants()
            ],
            relatedEvent: $event,
            relatedUser: $participant,
            priority: 'normal',
            category: 'participation'
        );
    }

    /**
     * Notifie la confirmation d'inscription au participant
     */
    public function createRegistrationConfirmedNotification(EventRegistration $registration): Notification
    {
        $event = $registration->getEvent();
        $participant = $registration->getUser();
        
        $message = sprintf(
            'Votre inscription à l\'événement "%s" est confirmée ! Rendez-vous le %s.',
            $event->getTitle(),
            $event->getStartDate()->format('d/m/Y à H:i')
        );

        return $this->create(
            user: $participant,
            type: Notification::TYPE_REGISTRATION_CONFIRMED,
            title: 'Inscription confirmée',
            message: $message,
            data: [
                'event_id' => $event->getId(),
                'registration_id' => $registration->getId(),
                'event_date' => $event->getStartDate()->format('c')
            ],
            relatedEvent: $event,
            priority: 'high',
            category: 'participation'
        );
    }

    /**
     * Notifications temporelles pour événements
     */
    public function createEventTemporalNotification(
        User $user,
        Event $event,
        string $type,
        string $title,
        string $message,
        string $priority = 'normal'
    ): Notification {
        return $this->create(
            user: $user,
            type: $type,
            title: $title,
            message: $message,
            data: [
                'event_id' => $event->getId(),
                'event_date' => $event->getStartDate()->format('c'),
                'event_end_date' => $event->getEndDate()?->format('c'),
                'is_online' => $event->isOnline()
            ],
            relatedEvent: $event,
            priority: $priority,
            category: 'events'
        );
    }

    // ============= MÉTHODES POUR NOTIFICATIONS EN LOT =============

    /**
     * Envoie une notification à tous les participants d'un événement
     */
    public function notifyEventParticipants(
        Event $event,
        string $type,
        string $title,
        string $message,
        string $priority = 'normal',
        ?array $additionalData = null
    ): int {
        $registrations = $this->registrationRepository->findActiveByEvent($event);
        $notificationsSent = 0;

        foreach ($registrations as $registration) {
            $participant = $registration->getUser();
            
            // Éviter les doublons récents
            if ($this->hasRecentNotification($participant, $event, $type)) {
                continue;
            }

            $this->createEventTemporalNotification(
                $participant,
                $event,
                $type,
                $title,
                $message,
                $priority
            );
            
            $notificationsSent++;
        }

        // Notifier aussi l'organisateur si différent
        $creator = $event->getCreatedBy();
        if ($creator && !$this->isUserInRegistrations($creator, $registrations)) {
            if (!$this->hasRecentNotification($creator, $event, $type)) {
                $this->createEventTemporalNotification(
                    $creator,
                    $event,
                    $type,
                    $title . ' (votre événement)',
                    $message,
                    $priority
                );
                $notificationsSent++;
            }
        }

        $this->logger->info('Notifications temporelles envoyées', [
            'type' => $type,
            'event_id' => $event->getId(),
            'notifications_sent' => $notificationsSent
        ]);

        return $notificationsSent;
    }

    /**
     * Traite toutes les notifications automatiques d'événements
     */
    public function processAutomaticEventNotifications(): array
    {
        $results = [
            'approaching' => $this->notifyEventsApproaching(),
            'soon' => $this->notifyEventsSoon(),
            'starting' => $this->notifyEventsStarting(),
            'ending_soon' => $this->notifyEventsEndingSoon(),
            'finished' => $this->notifyEventsFinished()
        ];

        $total = array_sum($results);
        
        $this->logger->info('Traitement automatique des notifications terminé', [
            'results' => $results,
            'total_sent' => $total
        ]);

        return $results;
    }

    /**
     * Événements qui approchent (7 jours avant)
     */
    public function notifyEventsApproaching(): int
    {
        $from = new \DateTimeImmutable('+6 days 23 hours');
        $to = new \DateTimeImmutable('+7 days 1 hour');
        
        $events = $this->eventRepository->findEventsInTimeRange($from, $to, ['APPROVED']);
        $notificationsSent = 0;
        
        foreach ($events as $event) {
            $count = $this->notifyEventParticipants(
                $event,
                Notification::TYPE_EVENT_APPROACHING,
                'Événement dans 7 jours',
                sprintf(
                    'L\'événement "%s" commence dans une semaine (%s).',
                    $event->getTitle(),
                    $event->getStartDate()->format('d/m/Y à H:i')
                ),
                'normal'
            );
            $notificationsSent += $count;
        }
        
        return $notificationsSent;
    }

    /**
     * Événements bientôt (2 jours avant)
     */
    public function notifyEventsSoon(): int
    {
        $from = new \DateTimeImmutable('+1 day 23 hours');
        $to = new \DateTimeImmutable('+2 days 1 hour');
        
        $events = $this->eventRepository->findEventsInTimeRange($from, $to, ['APPROVED']);
        $notificationsSent = 0;
        
        foreach ($events as $event) {
            $count = $this->notifyEventParticipants(
                $event,
                Notification::TYPE_EVENT_SOON,
                'Événement dans 2 jours',
                sprintf(
                    'L\'événement "%s" commence après-demain (%s). N\'oubliez pas !',
                    $event->getTitle(),
                    $event->getStartDate()->format('d/m/Y à H:i')
                ),
                'high'
            );
            $notificationsSent += $count;
        }
        
        return $notificationsSent;
    }

    /**
     * Événements qui commencent (1h avant)
     */
    public function notifyEventsStarting(): int
    {
        $from = new \DateTimeImmutable('+59 minutes');
        $to = new \DateTimeImmutable('+61 minutes');
        
        $events = $this->eventRepository->findEventsInTimeRange($from, $to, ['APPROVED']);
        $notificationsSent = 0;
        
        foreach ($events as $event) {
            $message = sprintf(
                'L\'événement "%s" commence dans 1 heure ! %s',
                $event->getTitle(),
                $event->isOnline() 
                    ? 'Préparez-vous à vous connecter.'
                    : 'N\'oubliez pas de vous rendre sur place.'
            );
            
            $count = $this->notifyEventParticipants(
                $event,
                Notification::TYPE_EVENT_STARTING,
                'Événement commence bientôt !',
                $message,
                'urgent'
            );
            $notificationsSent += $count;
        }
        
        return $notificationsSent;
    }

    /**
     * Événements qui se terminent bientôt (1h avant la fin)
     */
    public function notifyEventsEndingSoon(): int
    {
        $from = new \DateTimeImmutable('+59 minutes');
        $to = new \DateTimeImmutable('+61 minutes');
        
        $events = $this->eventRepository->findEventsEndingInTimeRange($from, $to, ['IN_PROGRESS']);
        $notificationsSent = 0;
        
        foreach ($events as $event) {
            $count = $this->notifyEventParticipants(
                $event,
                Notification::TYPE_EVENT_ENDING_SOON,
                'Événement se termine bientôt',
                sprintf(
                    'L\'événement "%s" se termine dans 1 heure. Profitez des derniers moments !',
                    $event->getTitle()
                ),
                'high'
            );
            $notificationsSent += $count;
        }
        
        return $notificationsSent;
    }

    /**
     * Événements terminés
     */
    public function notifyEventsFinished(): int
    {
        $from = new \DateTimeImmutable('-1 hour');
        $to = new \DateTimeImmutable();
        
        $events = $this->eventRepository->findEventsEndedInTimeRange($from, $to, ['IN_PROGRESS', 'FINISHED']);
        $notificationsSent = 0;
        
        foreach ($events as $event) {
            // Marquer l'événement comme terminé s'il ne l'est pas déjà
            if ($event->getStatus() !== Event::STATUS_FINISHED) {
                $event->finish();
            }
            
            $count = $this->notifyEventParticipants(
                $event,
                Notification::TYPE_EVENT_FINISHED,
                'Événement terminé',
                sprintf(
                    'L\'événement "%s" est maintenant terminé. Merci de votre participation !',
                    $event->getTitle()
                ),
                'normal'
            );
            $notificationsSent += $count;
        }
        
        if ($notificationsSent > 0) {
            $this->entityManager->flush();
        }
        
        return $notificationsSent;
    }

    // ============= MÉTHODES UTILITAIRES =============

    /**
     * Vérifie si une notification récente existe
     */
    private function hasRecentNotification(User $user, Event $event, string $type, int $hoursBack = 2): bool
    {
        $since = new \DateTimeImmutable("-{$hoursBack} hours");
        
        return $this->notificationRepository->existsRecentNotificationForUserAndEvent(
            $user,
            $event,
            $type,
            $since
        );
    }

    /**
     * Vérifie si un utilisateur est dans la liste des inscriptions
     */
    private function isUserInRegistrations(User $user, array $registrations): bool
    {
        foreach ($registrations as $registration) {
            if ($registration->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }

    // ============= MÉTHODES EXISTANTES (INCHANGÉES) =============

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->markAsRead();
            $this->entityManager->flush();
        }
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Récupère le nombre de notifications non lues
     */
    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnread($user);
    }

    /**
     * Récupère les notifications pour l'header (non lues uniquement)
     */
    public function getHeaderNotifications(User $user, int $limit = 4): array
    {
        return $this->notificationRepository->findUnreadForHeader($user, $limit);
    }

    /**
     * Récupère les notifications récentes pour ProfileView (paginées)
     */
    public function getRecentNotifications(User $user, int $offset = 0, int $limit = 6): array
    {
        return $this->notificationRepository->findRecentForProfile($user, $offset, $limit);
    }

        /**
     * Getter pour accès au EventRepository
     */
    public function getEventRepository(): EventRepository
    {
        return $this->eventRepository;
    }

    /**
     * Getter pour accès au NotificationRepository  
     */
    public function getNotificationRepository(): NotificationRepository
    {
        return $this->notificationRepository;
    }

    /**
     * Getter pour accès au EventRegistrationRepository
     */
    public function getEventRegistrationRepository(): EventRegistrationRepository
    {
        return $this->registrationRepository;
    }

    /**
     * Récupère toutes les notifications avec pagination
     */
    public function getUserNotifications(User $user, int $page = 1, int $limit = 10): array
    {
        $notifications = $this->notificationRepository->findByUserPaginated($user, $page, $limit);
        $total = $this->notificationRepository->countByUser($user);
        
        return [
            'notifications' => $notifications,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Supprime les anciennes notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        return $this->notificationRepository->deleteOldNotifications($daysOld);
    }

    /**
     * Récupère les statistiques des notifications d'un utilisateur
     */
    public function getNotificationStats(User $user): array
    {
        return $this->notificationRepository->getNotificationStats($user);
    }

    /**
     * Sérialise une notification pour l'API
     */
    public function serializeNotification(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'data' => $notification->getData(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('c'),
            'readAt' => $notification->getReadAt()?->format('c'),
            'timeAgo' => $notification->getTimeAgo(),
            'actionUrl' => $notification->getActionUrl(),
            'actionLabel' => $notification->getActionLabel(),
            'icon' => $notification->getIcon() ?: $notification->getDefaultIcon(),
            'typeLabel' => $notification->getTypeLabel(),
            'priority' => $notification->getPriority(),
            'category' => $notification->getCategory()
        ];
    }

    /**
     * Sérialise un tableau de notifications
     */
    public function serializeNotifications(array $notifications): array
    {
        return array_map([$this, 'serializeNotification'], $notifications);
    }

    /**
     * Formate les données pour l'header (version simplifiée)
     */
    public function serializeForHeader(array $notifications): array
    {
        return array_map(function (Notification $notification) {
            return [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'timeAgo' => $notification->getTimeAgo(),
                'icon' => $notification->getIcon() ?: $notification->getDefaultIcon(),
                'actionUrl' => $notification->getActionUrl(),
                'priority' => $notification->getPriority()
            ];
        }, $notifications);
    }

    /**
     * Formate les données pour ProfileView (version complète)
     */
    public function serializeForProfile(array $notifications): array
    {
        return array_map(function (Notification $notification) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'isRead' => $notification->isRead(),
                'timeAgo' => $notification->getTimeAgo(),
                'icon' => $notification->getIcon() ?: $notification->getDefaultIcon(),
                'actionUrl' => $notification->getActionUrl(),
                'actionLabel' => $notification->getActionLabel(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'priority' => $notification->getPriority(),
                'category' => $notification->getCategory()
            ];
        }, $notifications);
    }

    /**
     * Template pour futures notifications d'événements (DÉPRÉCIÉE - utiliser les nouvelles méthodes)
     */
    public function createEventNotification(User $user, array $eventData): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_EVENT_CREATED,
            title: 'Nouvel événement',
            message: "Un nouvel événement \"{$eventData['name']}\" a été créé près de chez vous !",
            data: [
                'event_id' => $eventData['id'],
                'event_name' => $eventData['name'],
                'shop_name' => $eventData['shop_name'] ?? null,
                'event_date' => $eventData['date'] ?? null
            ],
            actionUrl: "/events/{$eventData['id']}",
            actionLabel: 'Voir l\'événement',
            icon: '📅',
            category: 'events'
        );
    }

    /**
     * Template pour futures notifications de réponses (INCHANGÉE)
     */
    public function createReplyNotification(User $user, array $replyData): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_REPLY_RECEIVED,
            title: 'Nouvelle réponse',
            message: "Quelqu'un a répondu à votre topic \"{$replyData['topic_title']}\"",
            data: [
                'reply_id' => $replyData['id'],
                'topic_id' => $replyData['topic_id'],
                'topic_title' => $replyData['topic_title'],
                'author_name' => $replyData['author_name']
            ],
            actionUrl: "/topics/{$replyData['topic_id']}#{$replyData['id']}",
            actionLabel: 'Voir la réponse',
            icon: '💬',
            category: 'social'
        );
    }

    /**
     * Retourne le nom d'affichage d'un rôle
     */
    private function getRoleDisplayName(string $role): string
    {
        return match($role) {
            'ROLE_ORGANIZER' => 'Organisateur',
            'ROLE_SHOP' => 'Boutique',
            'ROLE_ADMIN' => 'Administrateur',
            default => 'Utilisateur'
        };
    }
}