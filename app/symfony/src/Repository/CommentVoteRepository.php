<?php

namespace App\Repository;

use App\Entity\CommentVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentVote::class);
    }

    public function countVotesForComment(int $commentId, string $type): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.comment = :commentId')
            ->andWhere('v.type = :type')
            ->setParameter('commentId', $commentId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
