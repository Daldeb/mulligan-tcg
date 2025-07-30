<?php

namespace App\Repository;

use App\Entity\RoleRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoleRequest>
 */
class RoleRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoleRequest::class);
    }

    /**
     * Trouve les demandes de rôle d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a une demande en cours pour un rôle spécifique
     */
    public function hasPendingRequestForRole(User $user, string $role): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.user = :user')
            ->andWhere('r.requestedRole = :role')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('role', $role)
            ->setParameter('status', RoleRequest::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Trouve toutes les demandes en attente pour les admins
     */
    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', RoleRequest::STATUS_PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des demandes pour le dashboard admin
     */
    public function getRequestStats(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
            $stats['total'] += (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Trouve les demandes récentes (pour notifications)
     */
    public function findRecentRequests(int $days = 7): array
    {
        $date = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}