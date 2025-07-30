<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'addresses')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'L\'adresse doit contenir au moins {{ limit }} caractères',
        maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $streetAddress = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom de ville doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom de ville ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[0-9]{5}$/',
        message: 'Le code postal doit contenir exactement 5 chiffres'
    )]
    private ?string $postalCode = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    private string $country = 'France';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(string $streetAddress): static
    {
        $this->streetAddress = $streetAddress;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Retourne l'adresse complète formatée
     */
    public function getFullAddress(): string
    {
        return sprintf(
            '%s, %s %s, %s',
            $this->streetAddress,
            $this->postalCode,
            $this->city,
            $this->country
        );
    }

    /**
     * Met à jour les coordonnées géographiques
     */
    public function setCoordinates(?float $latitude, ?float $longitude): static
    {
        $this->latitude = $latitude ? (string) $latitude : null;
        $this->longitude = $longitude ? (string) $longitude : null;
        return $this;
    }

    /**
     * Vérifie si l'adresse a des coordonnées
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}