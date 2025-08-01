<?php

namespace App\Repository;

use App\Entity\Forum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forum>
 *
 * @method Forum|null find($id, $lockMode = null, $lockVersion = null)
 * @method Forum|null findOneBy(array $criteria, array $orderBy = null)
 * @method Forum[]    findAll()
 * @method Forum[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    /**
     * Trouve un forum par son slug (cas d’usage API/route)
     */
    public function findOneBySlug(string $slug): ?Forum
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tous les forums officiels liés à un jeu
     */
    public function findOfficialForums(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.isOfficial = true')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
