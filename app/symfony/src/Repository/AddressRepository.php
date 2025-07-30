<?php

namespace App\Repository;

use App\Entity\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Trouve ou crée une adresse similaire
     */
    public function findOrCreateSimilar(
        string $streetAddress,
        string $city,
        string $postalCode,
        string $country = 'France'
    ): Address {
        // Chercher une adresse existante identique
        $existingAddress = $this->createQueryBuilder('a')
            ->andWhere('a.streetAddress = :street')
            ->andWhere('a.city = :city')
            ->andWhere('a.postalCode = :postalCode')
            ->andWhere('a.country = :country')
            ->setParameter('street', trim($streetAddress))
            ->setParameter('city', trim($city))
            ->setParameter('postalCode', trim($postalCode))
            ->setParameter('country', $country)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingAddress) {
            return $existingAddress;
        }

        // Créer une nouvelle adresse
        $address = new Address();
        $address->setStreetAddress(trim($streetAddress));
        $address->setCity(trim($city));
        $address->setPostalCode(trim($postalCode));
        $address->setCountry($country);

        $this->getEntityManager()->persist($address);
        
        return $address;
    }

    /**
     * Recherche d'adresses par proximité géographique
     */
    public function findByProximity(float $latitude, float $longitude, float $radiusKm = 10): array
    {
        // Calcul approximatif des limites géographiques
        $latitudeDelta = $radiusKm / 111; // 1 degré ≈ 111 km
        $longitudeDelta = $radiusKm / (111 * cos(deg2rad($latitude)));

        return $this->createQueryBuilder('a')
            ->andWhere('a.latitude BETWEEN :latMin AND :latMax')
            ->andWhere('a.longitude BETWEEN :lngMin AND :lngMax')
            ->andWhere('a.latitude IS NOT NULL')
            ->andWhere('a.longitude IS NOT NULL')
            ->setParameter('latMin', $latitude - $latitudeDelta)
            ->setParameter('latMax', $latitude + $latitudeDelta)
            ->setParameter('lngMin', $longitude - $longitudeDelta)
            ->setParameter('lngMax', $longitude + $longitudeDelta)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des adresses par département
     */
    public function getAddressStatsByDepartment(): array
    {
        return $this->createQueryBuilder('a')
            ->select('SUBSTRING(a.postalCode, 1, 2) as department, COUNT(a.id) as count')
            ->groupBy('department')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}