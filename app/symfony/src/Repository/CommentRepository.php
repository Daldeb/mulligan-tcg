<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Récupère les commentaires racine d’un post (ceux sans parent)
     */
    public function findTopLevelComments(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :postId')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.createdAt', 'ASC')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les commentaires d’un post triés par date (pour affichage simple)
     */
    public function findFlatComments(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :postId')
            ->orderBy('c.createdAt', 'ASC')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de topics distincts auxquels un utilisateur a participé (via commentaires)
     */
    public function countTopicsParticipatedByUser($user): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.post)')
            ->where('c.author = :user')
            ->andWhere('c.isDeleted = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
