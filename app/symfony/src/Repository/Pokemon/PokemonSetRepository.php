<?php

namespace App\Repository\Pokemon;

use App\Entity\Pokemon\PokemonSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PokemonSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PokemonSet::class);
    }

    /**
     * Trouver un set par son ID externe (ex: "sm9", "sv01")
     */
    public function findByExternalId(string $externalId): ?PokemonSet
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Récupérer tous les sets Pokemon triés par date de création
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('ps')
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les sets d'un jeu spécifique (Pokemon)
     */
    public function findByGame(int $gameId): array
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}