<?php

namespace App\Repository;

use App\Entity\PostSave;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostSave>
 *
 * @method PostSave|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostSave|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostSave[]    findAll()
 * @method PostSave[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostSaveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostSave::class);
    }

    /**
     * Vérifie si un utilisateur a sauvegardé un post spécifique
     */
    public function isPostSavedByUser(User $user, Post $post): bool
    {
        $save = $this->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        return $save !== null;
    }

    /**
     * Récupère tous les posts sauvegardés par un utilisateur
     * 
     * @param User $user
     * @param int|null $limit
     * @param int $offset
     * @return PostSave[]
     */
    public function findSavedPostsByUser(User $user, ?int $limit = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('ps')
            ->andWhere('ps.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ps.createdAt', 'DESC')
            ->setFirstResult($offset);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de posts sauvegardés par un utilisateur
     */
    public function countSavedPostsByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ps')
            ->select('COUNT(ps.id)')
            ->andWhere('ps.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les posts les plus sauvegardés (populaires)
     * 
     * @param int $limit
     * @return array Format: [['post' => Post, 'saves_count' => int], ...]
     */
    public function findMostSavedPosts(int $limit = 10): array
    {
        return $this->createQueryBuilder('ps')
            ->select('p as post, COUNT(ps.id) as saves_count')
            ->join('ps.post', 'p')
            ->groupBy('p.id')
            ->orderBy('saves_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte combien de fois un post a été sauvegardé
     */
    public function countSavesForPost(Post $post): int
    {
        return (int) $this->createQueryBuilder('ps')
            ->select('COUNT(ps.id)')
            ->andWhere('ps.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les sauvegardes récentes d'un utilisateur avec les infos des posts
     * 
     * @param User $user
     * @param int $limit
     * @return array Format enrichi avec données des posts
     */
    public function findRecentSavedPostsWithDetails(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('ps')
            ->select('ps', 'p', 'u', 'f')
            ->join('ps.post', 'p')
            ->join('p.author', 'u')
            ->join('p.forum', 'f')
            ->andWhere('ps.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ps.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime toutes les sauvegardes d'un utilisateur (GDPR)
     */
    public function deleteAllSavesByUser(User $user): int
    {
        return $this->createQueryBuilder('ps')
            ->delete()
            ->andWhere('ps.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime toutes les sauvegardes d'un post (quand le post est supprimé)
     */
    public function deleteAllSavesForPost(Post $post): int
    {
        return $this->createQueryBuilder('ps')
            ->delete()
            ->andWhere('ps.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les statistiques de sauvegarde pour un forum
     */
    public function getSaveStatsByForum(string $forumSlug): array
    {
        return $this->createQueryBuilder('ps')
            ->select('COUNT(ps.id) as total_saves, COUNT(DISTINCT ps.user) as unique_users')
            ->join('ps.post', 'p')
            ->join('p.forum', 'f')
            ->andWhere('f.slug = :forumSlug')
            ->setParameter('forumSlug', $forumSlug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les posts sauvegardés d'un utilisateur dans un forum spécifique
     */
    public function findSavedPostsByUserInForum(User $user, string $forumSlug, int $limit = 10): array
    {
        return $this->createQueryBuilder('ps')
            ->select('ps', 'p')
            ->join('ps.post', 'p')
            ->join('p.forum', 'f')
            ->andWhere('ps.user = :user')
            ->andWhere('f.slug = :forumSlug')
            ->setParameter('user', $user)
            ->setParameter('forumSlug', $forumSlug)
            ->orderBy('ps.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}