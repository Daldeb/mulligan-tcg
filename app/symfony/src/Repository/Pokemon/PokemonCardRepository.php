<?php

namespace App\Repository\Pokemon;

use App\Entity\Pokemon\PokemonCard;
use App\Entity\Pokemon\PokemonSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PokemonCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PokemonCard::class);
    }

    /**
     * Trouver une carte par son ID externe (ex: "sm9-1")
     */
    public function findByExternalId(string $externalId): ?PokemonCard
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Récupérer les cartes Standard légales
     */
    public function findStandardLegal(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.isStandardLegal = :legal')
            ->setParameter('legal', true)
            ->orderBy('pc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les cartes d'un set spécifique
     */
    public function findBySet(PokemonSet $set): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.pokemonSet = :set')
            ->setParameter('set', $set)
            ->orderBy('pc.localId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher des cartes par nom
     */
    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('pc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtrer par type (ex: "Plante", "Feu")
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('pc')
            ->where('JSON_CONTAINS(pc.types, :type) = 1')
            ->setParameter('type', json_encode($type))
            ->orderBy('pc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}