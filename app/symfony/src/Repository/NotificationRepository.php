<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Trouve les notifications non lues pour l'header (4 max)
     */
    public function findUnreadForHeader(User $user, int $limit = 4): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :read')
            ->setParameter('user', $user)
            ->setParameter('read', false)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
 * Vérifie si une notification récente existe pour un utilisateur et un événement
 */
public function existsRecentNotificationForUserAndEvent(
    User $user, 
    Event $event, 
    string $type, 
    \DateTimeImmutable $since
): bool {
    $count = $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.user = :user')
        ->andWhere('n.relatedEvent = :event')
        ->andWhere('n.type = :type')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('user', $user)
        ->setParameter('event', $event)
        ->setParameter('type', $type)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();

    return $count > 0;
}

/**
 * Vérifie si une notification récente existe pour un événement (tous utilisateurs)
 */
public function existsRecentNotificationForEvent(
    Event $event, 
    string $type, 
    \DateTimeImmutable $since
): bool {
    $count = $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.relatedEvent = :event')
        ->andWhere('n.type = :type')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('event', $event)
        ->setParameter('type', $type)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();

    return $count > 0;
}

/**
 * Supprime les notifications plus anciennes qu'une date
 */
public function deleteOlderThan(\DateTimeImmutable $cutoffDate): int
{
    return $this->createQueryBuilder('n')
        ->delete()
        ->andWhere('n.createdAt < :cutoffDate')
        ->setParameter('cutoffDate', $cutoffDate)
        ->getQuery()
        ->execute();
}

/**
 * Compte les notifications depuis une date
 */
public function countSince(\DateTimeImmutable $since): int
{
    return $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Compte les notifications par type depuis une date
 */
public function countByTypeSince(\DateTimeImmutable $since): array
{
    $result = $this->createQueryBuilder('n')
        ->select('n.type, COUNT(n.id) as count')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('since', $since)
        ->groupBy('n.type')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();

    $stats = [];
    foreach ($result as $row) {
        $stats[$row['type']] = (int) $row['count'];
    }

    return $stats;
}

/**
 * Compte les notifications par catégorie depuis une date
 */
public function countByCategorySince(\DateTimeImmutable $since): array
{
    $result = $this->createQueryBuilder('n')
        ->select('n.category, COUNT(n.id) as count')
        ->andWhere('n.createdAt >= :since')
        ->andWhere('n.category IS NOT NULL')
        ->setParameter('since', $since)
        ->groupBy('n.category')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();

    $stats = [];
    foreach ($result as $row) {
        $stats[$row['category']] = (int) $row['count'];
    }

    return $stats;
}

/**
 * Retourne le taux de notifications non lues depuis une date
 */
public function getUnreadRateSince(\DateTimeImmutable $since): float
{
    $total = $this->countSince($since);
    
    if ($total === 0) {
        return 0.0;
    }

    $unread = $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.createdAt >= :since')
        ->andWhere('n.isRead = false')
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();

    return $unread / $total;
}

/**
 * Trouve les notifications par type pour un utilisateur
 */
public function findByType(User $user, string $type, int $limit = 10): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.user = :user')
        ->andWhere('n.type = :type')
        ->setParameter('user', $user)
        ->setParameter('type', $type)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

/**
 * Trouve les notifications par catégorie pour un utilisateur
 */
public function findByCategory(User $user, string $category, int $limit = 10): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.user = :user')
        ->andWhere('n.category = :category')
        ->setParameter('user', $user)
        ->setParameter('category', $category)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

/**
 * Trouve les notifications par priorité pour un utilisateur
 */
public function findByPriority(User $user, string $priority, int $limit = 10): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.user = :user')
        ->andWhere('n.priority = :priority')
        ->setParameter('user', $user)
        ->setParameter('priority', $priority)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

/**
 * Trouve les notifications urgentes non lues
 */
public function findUrgentUnread(User $user, int $limit = 10): array
{
    return $this->createQueryBuilder('n')
        ->andWhere('n.user = :user')
        ->andWhere('n.isRead = false')
        ->andWhere('n.priority = :urgent')
        ->setParameter('user', $user)
        ->setParameter('urgent', 'urgent')
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

/**
 * Marque toutes les notifications comme lues pour un utilisateur
 */
public function markAllAsReadForUser(User $user): int
{
    $updated = $this->createQueryBuilder('n')
        ->update()
        ->set('n.isRead', 'true')
        ->set('n.readAt', ':readAt')
        ->andWhere('n.user = :user')
        ->andWhere('n.isRead = false')
        ->setParameter('user', $user)
        ->setParameter('readAt', new \DateTimeImmutable())
        ->getQuery()
        ->execute();

    return $updated;
}

/**
 * Statistiques complètes des notifications d'un utilisateur
 */
public function getNotificationStats(User $user): array
{
    // Total
    $total = $this->countByUser($user);
    
    // Non lues
    $unread = $this->countUnread($user);
    
    // Par type
    $byType = $this->createQueryBuilder('n')
        ->select('n.type, COUNT(n.id) as count')
        ->andWhere('n.user = :user')
        ->setParameter('user', $user)
        ->groupBy('n.type')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();

    $typeStats = [];
    foreach ($byType as $row) {
        $typeStats[$row['type']] = (int) $row['count'];
    }

    // Par catégorie
    $byCategory = $this->createQueryBuilder('n')
        ->select('n.category, COUNT(n.id) as count')
        ->andWhere('n.user = :user')
        ->andWhere('n.category IS NOT NULL')
        ->setParameter('user', $user)
        ->groupBy('n.category')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();

    $categoryStats = [];
    foreach ($byCategory as $row) {
        $categoryStats[$row['category']] = (int) $row['count'];
    }

    // Par priorité
    $byPriority = $this->createQueryBuilder('n')
        ->select('n.priority, COUNT(n.id) as count')
        ->andWhere('n.user = :user')
        ->setParameter('user', $user)
        ->groupBy('n.priority')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();

    $priorityStats = [];
    foreach ($byPriority as $row) {
        $priorityStats[$row['priority']] = (int) $row['count'];
    }

    // Récentes (7 derniers jours)
    $since = new \DateTimeImmutable('-7 days');
    $recent = $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->andWhere('n.user = :user')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('user', $user)
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();

    return [
        'total' => $total,
        'unread' => $unread,
        'recent_7_days' => $recent,
        'read_percentage' => $total > 0 ? round((($total - $unread) / $total) * 100, 1) : 0,
        'by_type' => $typeStats,
        'by_category' => $categoryStats,
        'by_priority' => $priorityStats
    ];
}

/**
 * Supprime les anciennes notifications lues
 */
public function deleteOldNotifications(int $daysOld = 30): int
{
    $cutoffDate = new \DateTimeImmutable("-{$daysOld} days");
    
    return $this->createQueryBuilder('n')
        ->delete()
        ->andWhere('n.isRead = true')
        ->andWhere('n.createdAt < :cutoffDate')
        ->setParameter('cutoffDate', $cutoffDate)
        ->getQuery()
        ->execute();
}

/**
 * Trouve les notifications liées à un événement
 */
public function findByRelatedEvent(Event $event, int $limit = null): array
{
    $qb = $this->createQueryBuilder('n')
        ->andWhere('n.relatedEvent = :event')
        ->setParameter('event', $event)
        ->orderBy('n.createdAt', 'DESC');

    if ($limit) {
        $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Nettoie les notifications orphelines (événements supprimés)
 */
public function cleanupOrphanNotifications(): int
{
    // Supprimer les notifications liées à des événements qui n'existent plus
    $deleted = $this->_em->createQuery('
        DELETE FROM App\Entity\Notification n 
        WHERE n.relatedEvent IS NOT NULL 
        AND n.relatedEvent NOT IN (
            SELECT e.id FROM App\Entity\Event e
        )
    ')->execute();

    return $deleted;
}

    /**
     * Compte le nombre de notifications non lues
     */
    public function countUnread(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :read')
            ->setParameter('user', $user)
            ->setParameter('read', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les notifications récentes pour ProfileView (paginées)
     */
    public function findRecentForProfile(User $user, int $offset = 0, int $limit = 6): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les notifications d'un utilisateur (avec pagination)
     */
    public function findByUserPaginated(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le total de notifications d'un utilisateur
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les notifications récentes (24h) pour un utilisateur
     */
    public function findTodayNotifications(User $user): array
    {
        $todayStart = new \DateTimeImmutable('today');
        
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.createdAt >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $todayStart)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

}