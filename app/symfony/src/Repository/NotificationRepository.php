<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
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
     * Marque toutes les notifications non lues comme lues
     */
    public function markAllAsReadForUser(User $user): int
    {
        $qb = $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':read')
            ->set('n.readAt', ':readAt')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :notRead')
            ->setParameter('read', true)
            ->setParameter('readAt', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('notRead', false);

        return $qb->getQuery()->execute();
    }

    /**
     * Supprime les anciennes notifications (plus de X jours)
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysOld} days");
        
        return $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('n.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouve les notifications par type
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

    /**
     * Statistiques des notifications pour un utilisateur
     */
    public function getNotificationStats(User $user): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('n.type, COUNT(n.id) as count, SUM(CASE WHEN n.isRead = false THEN 1 ELSE 0 END) as unread')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->groupBy('n.type');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            'total' => 0,
            'unread' => 0,
            'by_type' => []
        ];

        foreach ($results as $result) {
            $stats['total'] += (int) $result['count'];
            $stats['unread'] += (int) $result['unread'];
            $stats['by_type'][$result['type']] = [
                'total' => (int) $result['count'],
                'unread' => (int) $result['unread']
            ];
        }

        return $stats;
    }
}