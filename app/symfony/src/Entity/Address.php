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

    // ============= GETTERS & SETTERS =============

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

    public function getLatitude(): ?float
    {
        return $this->latitude !== null ? (float) $this->latitude : null;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude !== null ? (float) $this->longitude : null;
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

    // ============= MÉTHODES UTILITAIRES =============

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

    /**
     * Retourne les coordonnées sous forme de tableau
     */
    public function getCoordinates(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => $this->getLatitude(),
            'lng' => $this->getLongitude()
        ];
    }

    /**
     * Retourne une adresse courte (ville + code postal)
     */
    public function getShortAddress(): string
    {
        return sprintf('%s %s', $this->postalCode, $this->city);
    }

    /**
     * Retourne le département (2 premiers chiffres du code postal)
     */
    public function getDepartment(): ?string
    {
        if (!$this->postalCode || strlen($this->postalCode) < 2) {
            return null;
        }

        return substr($this->postalCode, 0, 2);
    }

    /**
     * Vérifie si l'adresse est en France métropolitaine
     */
    public function isInMetropolitanFrance(): bool
    {
        if ($this->country !== 'France') {
            return false;
        }

        $department = $this->getDepartment();
        if (!$department) {
            return false;
        }

        // Codes postaux métropolitains (01-95, sauf 20 = Corse qui est considérée comme métropolitaine)
        $departmentCode = (int) $department;
        return $departmentCode >= 1 && $departmentCode <= 95;
    }

    /**
     * Retourne la région approximative basée sur le département
     */
    public function getRegion(): ?string
    {
        $department = $this->getDepartment();
        if (!$department) {
            return null;
        }

        $regions = [
            'Île-de-France' => ['75', '77', '78', '91', '92', '93', '94', '95'],
            'Auvergne-Rhône-Alpes' => ['01', '03', '07', '15', '26', '38', '42', '43', '63', '69', '73', '74'],
            'Bourgogne-Franche-Comté' => ['21', '25', '39', '58', '70', '71', '89', '90'],
            'Bretagne' => ['22', '29', '35', '56'],
            'Centre-Val de Loire' => ['18', '28', '36', '37', '41', '45'],
            'Corse' => ['2A', '2B'],
            'Grand Est' => ['08', '10', '51', '52', '54', '55', '57', '67', '68', '88'],
            'Hauts-de-France' => ['02', '59', '60', '62', '80'],
            'Normandie' => ['14', '27', '50', '61', '76'],
            'Nouvelle-Aquitaine' => ['16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87'],
            'Occitanie' => ['09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82'],
            'Pays de la Loire' => ['44', '49', '53', '72', '85'],
            'Provence-Alpes-Côte d\'Azur' => ['04', '05', '06', '13', '83', '84']
        ];

        foreach ($regions as $region => $departments) {
            if (in_array($department, $departments, true)) {
                return $region;
            }
        }

        return 'Région inconnue';
    }
}