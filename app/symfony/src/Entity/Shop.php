<?php

namespace App\Entity;

use App\Repository\ShopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShopRepository::class)]
#[ORM\Table(name: 'shop')]
class Shop
{
    // Types de boutique
    public const TYPE_SCRAPED = 'scraped';     // Boutique scrappée depuis API/base de données
    public const TYPE_REGISTERED = 'registered'; // Boutique créée par inscription
    public const TYPE_VERIFIED = 'verified';   // Boutique scrappée puis revendiquée et vérifiée

    // Status de vérification
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom de la boutique
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la boutique est requis')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $name = null;

    /**
     * Slug unique pour URL
     */
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    /**
     * Description de la boutique
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Type de boutique (scraped, registered, verified)
     */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_SCRAPED, self::TYPE_REGISTERED, self::TYPE_VERIFIED])]
    private string $type = self::TYPE_SCRAPED;

    /**
     * Status de vérification
     */
    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_VERIFIED, self::STATUS_REJECTED, self::STATUS_SUSPENDED])]
    private string $status = self::STATUS_PENDING;

    /**
     * Propriétaire de la boutique (NULL pour boutiques scrappées non revendiquées)
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'shop')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    /**
     * Adresse physique de la boutique (OBLIGATOIRE)
     */
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'L\'adresse de la boutique est obligatoire')]
    private ?Address $address = null;

    /**
     * Téléphone de la boutique
     */
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/',
        message: 'Numéro de téléphone français invalide'
    )]
    private ?string $phone = null;

    /**
     * Site web de la boutique
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du site web n\'est pas valide')]
    private ?string $website = null;

    /**
     * Email de contact de la boutique
     */
    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    private ?string $email = null;

    /**
     * Numéro SIRET (pour boutiques françaises)
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $siretNumber = null;

    /**
     * Logo/Image de la boutique
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * Couleur principale de la boutique (branding)
     */
    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9a-fA-F]{6}$/',
        message: 'La couleur doit être au format hexadécimal (#RRGGBB)'
    )]
    private ?string $primaryColor = null;

    /**
     * Horaires d'ouverture (format JSON)
     * Structure: {"monday": {"open": "09:00", "close": "18:00"}, ...}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $openingHours = null;

    /**
     * Services proposés par la boutique
     * Ex: ["tournament", "deck_building", "singles", "accessories"]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $services = null;

    /**
     * Jeux spécialisés de la boutique (IDs des jeux)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $specializedGames = null;

    /**
     * Données de scrapping (Google Place ID, etc.)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $scrapingData = null;

    /**
     * Données de vérification SIRET/API
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $verificationData = null;

    /**
     * Score de confiance (0-100)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $confidenceScore = null;

    /**
     * Statistiques de la boutique
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $stats = null;

    /**
     * Configuration spéciale de la boutique
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $config = null;

    /**
     * Boutique active/visible
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    /**
     * Boutique mise en avant
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isFeatured = false;

    /**
     * Ordre d'affichage
     */
    #[ORM\Column(type: 'integer')]
    private int $displayOrder = 0;

    /**
     * Date de création
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Date de dernière vérification
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastVerifiedAt = null;

    /**
     * Date de revendication (quand owner a été défini)
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->services = [];
        $this->specializedGames = [];
        $this->stats = [
            'views' => 0,
            'events_created' => 0,
            'tournaments_hosted' => 0,
            'rating' => 0.0,
            'reviews_count' => 0
        ];
    }

    // ============= GETTERS & SETTERS =============

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updateSlug();
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        
        // Si on définit un owner, c'est une revendication
        if ($owner && !$this->claimedAt) {
            $this->claimedAt = new \DateTimeImmutable();
            if ($this->type === self::TYPE_SCRAPED) {
                $this->type = self::TYPE_VERIFIED;
            }
        }
        
        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getSiretNumber(): ?string
    {
        return $this->siretNumber;
    }

    public function setSiretNumber(?string $siretNumber): static
    {
        $this->siretNumber = $siretNumber;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getOpeningHours(): ?array
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?array $openingHours): static
    {
        $this->openingHours = $openingHours;
        return $this;
    }

    public function getServices(): ?array
    {
        return $this->services;
    }

    public function setServices(?array $services): static
    {
        $this->services = $services ?? [];
        return $this;
    }

    public function getSpecializedGames(): ?array
    {
        return $this->specializedGames;
    }

    public function setSpecializedGames(?array $specializedGames): static
    {
        $this->specializedGames = $specializedGames ?? [];
        return $this;
    }

    public function getScrapingData(): ?array
    {
        return $this->scrapingData;
    }

    public function setScrapingData(?array $scrapingData): static
    {
        $this->scrapingData = $scrapingData;
        return $this;
    }

    public function getVerificationData(): ?array
    {
        return $this->verificationData;
    }

    public function setVerificationData(?array $verificationData): static
    {
        $this->verificationData = $verificationData;
        return $this;
    }

    public function getConfidenceScore(): ?int
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(?int $confidenceScore): static
    {
        $this->confidenceScore = $confidenceScore;
        return $this;
    }

    public function getStats(): ?array
    {
        return $this->stats;
    }

    public function setStats(?array $stats): static
    {
        $this->stats = $stats;
        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): static
    {
        $this->config = $config;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLastVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastVerifiedAt;
    }

    public function setLastVerifiedAt(?\DateTimeImmutable $lastVerifiedAt): static
    {
        $this->lastVerifiedAt = $lastVerifiedAt;
        return $this;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function setClaimedAt(?\DateTimeImmutable $claimedAt): static
    {
        $this->claimedAt = $claimedAt;
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES =============

    /**
     * Met à jour le timestamp
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Génère automatiquement le slug basé sur le nom
     */
    private function updateSlug(): void
    {
        if ($this->name) {
            $slug = strtolower($this->name);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            $this->slug = $slug;
        }
    }

    /**
     * Vérifie si la boutique est revendiquée
     */
    public function isClaimed(): bool
    {
        return $this->owner !== null;
    }

    /**
     * Vérifie si la boutique est scrappée
     */
    public function isScraped(): bool
    {
        return $this->type === self::TYPE_SCRAPED;
    }

    /**
     * Vérifie si la boutique est inscrite
     */
    public function isRegistered(): bool
    {
        return $this->type === self::TYPE_REGISTERED;
    }

    /**
     * Vérifie si la boutique est vérifiée
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Retourne l'adresse complète formatée
     */
    public function getFullAddress(): ?string
    {
        return $this->address?->getFullAddress();
    }

    /**
     * Retourne les coordonnées GPS
     */
    public function getCoordinates(): ?array
    {
        if (!$this->address) {
            return null;
        }

        return [
            'lat' => $this->address->getLatitude(),
            'lng' => $this->address->getLongitude()
        ];
    }

    /**
     * Vérifie si un service est proposé
     */
    public function hasService(string $service): bool
    {
        return in_array($service, $this->services ?? [], true);
    }

    /**
     * Ajoute un service
     */
    public function addService(string $service): static
    {
        if (!$this->hasService($service)) {
            $this->services[] = $service;
        }
        return $this;
    }

    /**
     * Supprime un service
     */
    public function removeService(string $service): static
    {
        $this->services = array_values(array_filter(
            $this->services ?? [],
            fn(string $s) => $s !== $service
        ));
        return $this;
    }

    /**
     * Vérifie si la boutique est spécialisée dans un jeu
     */
    public function isSpecializedInGame(int $gameId): bool
    {
        return in_array($gameId, $this->specializedGames ?? [], true);
    }

    /**
     * Met à jour les statistiques
     */
    public function updateStats(array $newStats): static
    {
        $this->stats = array_merge($this->stats ?? [], $newStats);
        return $this;
    }

    /**
     * Incrémente une statistique
     */
    public function incrementStat(string $stat, int $amount = 1): static
    {
        $stats = $this->stats ?? [];
        $stats[$stat] = ($stats[$stat] ?? 0) + $amount;
        $this->stats = $stats;
        return $this;
    }

    /**
     * Retourne la note moyenne
     */
    public function getAverageRating(): float
    {
        return (float) ($this->stats['rating'] ?? 0.0);
    }

    /**
     * Retourne le nombre de vues
     */
    public function getViewsCount(): int
    {
        return (int) ($this->stats['views'] ?? 0);
    }

    /**
     * Marque la boutique comme vérifiée
     */
    public function markAsVerified(): static
    {
        $this->status = self::STATUS_VERIFIED;
        $this->lastVerifiedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Rejette la vérification
     */
    public function rejectVerification(): static
    {
        $this->status = self::STATUS_REJECTED;
        return $this;
    }

    /**
     * Suspend la boutique
     */
    public function suspend(): static
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->isActive = false;
        return $this;
    }

    /**
     * Active/réactive la boutique
     */
    public function activate(): static
    {
        $this->isActive = true;
        if ($this->status === self::STATUS_SUSPENDED) {
            $this->status = self::STATUS_VERIFIED;
        }
        return $this;
    }
}