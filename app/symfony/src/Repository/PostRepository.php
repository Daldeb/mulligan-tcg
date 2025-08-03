<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Récupère les derniers posts, filtrables par forum (optionnel)
     */
    public function findLatestPosts(?int $forumId = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($forumId !== null) {
            $qb->andWhere('p.forum = :forumId')
               ->setParameter('forumId', $forumId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les posts populaires (par score)
     */
    public function findTopPosts(?int $forumId = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.score', 'DESC')
            ->setMaxResults($limit);

        if ($forumId !== null) {
            $qb->andWhere('p.forum = :forumId')
               ->setParameter('forumId', $forumId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les posts d'un utilisateur avec pagination
     */
    public function findByUserWithPagination($user, int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.author = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de posts d'un utilisateur
     */
    public function countByUser($user): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de topics créés par un utilisateur
     */
    public function countTopicsCreatedByUser($user): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
