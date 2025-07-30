<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\RoleRequest;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
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
        ?string $icon = null
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setData($data)
            ->setActionUrl($actionUrl)
            ->setActionLabel($actionLabel)
            ->setIcon($icon);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

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
            icon: '🎉'
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
            icon: '❌'
        );
    }

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
            'icon' => $notification->getIcon(),
            'typeLabel' => $notification->getTypeLabel()
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
                'icon' => $notification->getIcon(),
                'actionUrl' => $notification->getActionUrl()
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
                'icon' => $notification->getIcon(),
                'actionUrl' => $notification->getActionUrl(),
                'actionLabel' => $notification->getActionLabel(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $notifications);
    }

    /**
     * Template pour futures notifications d'événements
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
            icon: '📅'
        );
    }

    /**
     * Template pour futures notifications de réponses
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
            icon: '💬'
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