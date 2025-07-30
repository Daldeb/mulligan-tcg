<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications', name: 'api_notifications_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationManager $notificationManager,
        private NotificationRepository $notificationRepository
    ) {}

    /**
     * Récupère les notifications pour l'header (4 non lues max)
     */
    #[Route('/header', name: 'header', methods: ['GET'])]
    public function getHeaderNotifications(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $notifications = $this->notificationManager->getHeaderNotifications($user, 4);
        $unreadCount = $this->notificationManager->getUnreadCount($user);
        
        return $this->json([
            'notifications' => $this->notificationManager->serializeForHeader($notifications),
            'unreadCount' => $unreadCount,
            'hasMore' => $unreadCount > 4
        ]);
    }

    /**
     * Récupère les notifications récentes pour ProfileView (paginées)
     */
    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function getRecentNotifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(20, (int) $request->query->get('limit', 6))); // Max 20 par page
        $offset = ($page - 1) * $limit;
        
        $notifications = $this->notificationManager->getRecentNotifications($user, $offset, $limit);
        $total = $this->notificationRepository->countByUser($user);
        
        return $this->json([
            'notifications' => $this->notificationManager->serializeForProfile($notifications),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'hasMore' => $offset + $limit < $total
            ]
        ]);
    }

    /**
     * Récupère toutes les notifications avec pagination complète
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getAllNotifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 10))); // Max 50 par page
        
        $result = $this->notificationManager->getUserNotifications($user, $page, $limit);
        
        return $this->json([
            'notifications' => $this->notificationManager->serializeNotifications($result['notifications']),
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'hasMore' => $page < $result['pages']
            ]
        ]);
    }

    /**
     * Compte les notifications non lues
     */
    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $count = $this->notificationManager->getUnreadCount($user);
        
        return $this->json([
            'unreadCount' => $count,
            'hasUnread' => $count > 0
        ]);
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $notification = $this->notificationRepository->find($id);
        
        if (!$notification) {
            return $this->json(['error' => 'Notification non trouvée'], 404);
        }
        
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }
        
        if (!$notification->isRead()) {
            $this->notificationManager->markAsRead($notification);
        }
        
        return $this->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $this->notificationManager->serializeNotification($notification),
            'newUnreadCount' => $this->notificationManager->getUnreadCount($user)
        ]);
    }

    /**
     * Marque toutes les notifications comme lues
     */
    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $markedCount = $this->notificationManager->markAllAsRead($user);
        
        return $this->json([
            'message' => 'Toutes les notifications ont été marquées comme lues',
            'markedCount' => $markedCount,
            'newUnreadCount' => 0
        ]);
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteNotification(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $notification = $this->notificationRepository->find($id);
        
        if (!$notification) {
            return $this->json(['error' => 'Notification non trouvée'], 404);
        }
        
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }
        
        $entityManager = $this->notificationRepository->getEntityManager();
        $entityManager->remove($notification);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Notification supprimée',
            'newUnreadCount' => $this->notificationManager->getUnreadCount($user)
        ]);
    }

    /**
     * Statistiques des notifications de l'utilisateur
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getNotificationStats(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $stats = $this->notificationManager->getNotificationStats($user);
        
        return $this->json([
            'stats' => $stats,
            'summary' => [
                'total' => $stats['total'],
                'unread' => $stats['unread'],
                'readPercentage' => $stats['total'] > 0 ? round((($stats['total'] - $stats['unread']) / $stats['total']) * 100, 1) : 0
            ]
        ]);
    }

    /**
     * Récupère les notifications par type
     */
    #[Route('/type/{type}', name: 'by_type', methods: ['GET'])]
    public function getNotificationsByType(string $type, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Valider le type
        if (!in_array($type, Notification::getAvailableTypes())) {
            return $this->json(['error' => 'Type de notification invalide'], 400);
        }
        
        $limit = max(1, min(50, (int) $request->query->get('limit', 10)));
        $notifications = $this->notificationRepository->findByType($user, $type, $limit);
        
        return $this->json([
            'notifications' => $this->notificationManager->serializeNotifications($notifications),
            'type' => $type,
            'typeLabel' => $notifications[0]->getTypeLabel() ?? 'Notification',
            'count' => count($notifications)
        ]);
    }

    /**
     * Polling endpoint optimisé pour les mises à jour temps réel
     */
    #[Route('/poll', name: 'poll', methods: ['GET'])]
    public function pollNotifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Timestamp de la dernière vérification (pour optimiser)
        $lastCheck = $request->query->get('since');
        $sinceDate = null;
        
        if ($lastCheck) {
            try {
                $sinceDate = new \DateTimeImmutable('@' . $lastCheck);
            } catch (\Exception $e) {
                // Ignorer si timestamp invalide
            }
        }
        
        $unreadCount = $this->notificationManager->getUnreadCount($user);
        $headerNotifications = $this->notificationManager->getHeaderNotifications($user, 4);
        
        $response = [
            'unreadCount' => $unreadCount,
            'hasUnread' => $unreadCount > 0,
            'headerNotifications' => $this->notificationManager->serializeForHeader($headerNotifications),
            'timestamp' => time(),
            'hasChanges' => true // Pour l'instant toujours true, on optimisera plus tard
        ];
        
        // Si on a une date de référence, on pourrait optimiser en ne renvoyant que les nouvelles
        if ($sinceDate) {
            // TODO: Implémenter la détection de changements pour optimiser le polling
            // Pour l'instant on renvoie toujours tout
        }
        
        return $this->json($response);
    }
}