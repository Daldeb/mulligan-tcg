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
     * Trouve un forum par son slug (cas d'usage API/route)
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

    /**
     * Trouve les forums associés aux jeux sélectionnés par l'utilisateur
     */
    public function findByGameIds(array $gameIds): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.game IN (:gameIds)')
            ->orWhere('f.game IS NULL') // Inclure les forums sans jeu associé
            ->setParameter('gameIds', $gameIds)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}