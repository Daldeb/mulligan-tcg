<?php

namespace App\Repository;

use App\Entity\PostVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostVote::class);
    }

    public function countVotesForPost(int $postId, string $type): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.post = :postId')
            ->andWhere('v.type = :type')
            ->setParameter('postId', $postId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
