<?php

namespace App\Entity;

use App\Repository\RoleRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoleRequestRepository::class)]
#[ORM\Table(name: 'role_request')]
class RoleRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const ROLE_ORGANIZER = 'ROLE_ORGANIZER';
    public const ROLE_SHOP = 'ROLE_SHOP';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [self::ROLE_ORGANIZER, self::ROLE_SHOP])]
    private ?string $requestedRole = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminResponse = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    // Informations additionnelles pour les demandes de boutique
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $shopName = null;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Address $shopAddress = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $shopPhone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $shopWebsite = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $siretNumber = null;

    // 🆕 NOUVELLES COLONNES POUR VÉRIFICATION AUTOMATIQUE
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sirenData = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $verificationScore = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verificationDate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googlePlaceId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters existants

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRequestedRole(): ?string
    {
        return $this->requestedRole;
    }

    public function setRequestedRole(string $requestedRole): static
    {
        $this->requestedRole = $requestedRole;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getAdminResponse(): ?string
    {
        return $this->adminResponse;
    }

    public function setAdminResponse(?string $adminResponse): static
    {
        $this->adminResponse = $adminResponse;
        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;
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

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
        return $this;
    }

    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    public function setShopName(?string $shopName): static
    {
        $this->shopName = $shopName;
        return $this;
    }

    public function getShopAddress(): ?Address
    {
        return $this->shopAddress;
    }

    public function setShopAddress(?Address $shopAddress): static
    {
        $this->shopAddress = $shopAddress;
        return $this;
    }

    public function getShopAddressString(): ?string
    {
        return $this->shopAddress?->getFullAddress();
    }

    public function getShopPhone(): ?string
    {
        return $this->shopPhone;
    }

    public function setShopPhone(?string $shopPhone): static
    {
        $this->shopPhone = $shopPhone;
        return $this;
    }

    public function getShopWebsite(): ?string
    {
        return $this->shopWebsite;
    }

    public function setShopWebsite(?string $shopWebsite): static
    {
        $this->shopWebsite = $shopWebsite;
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

    // 🆕 GETTERS/SETTERS POUR NOUVELLES COLONNES

    public function getSirenData(): ?array
    {
        return $this->sirenData;
    }

    public function setSirenData(?array $sirenData): static
    {
        $this->sirenData = $sirenData;
        return $this;
    }

    public function getVerificationScore(): ?int
    {
        return $this->verificationScore;
    }

    public function setVerificationScore(?int $verificationScore): static
    {
        $this->verificationScore = $verificationScore;
        return $this;
    }

    public function getVerificationDate(): ?\DateTimeImmutable
    {
        return $this->verificationDate;
    }

    public function setVerificationDate(?\DateTimeImmutable $verificationDate): static
    {
        $this->verificationDate = $verificationDate;
        return $this;
    }

    public function getGooglePlaceId(): ?string
    {
        return $this->googlePlaceId;
    }

    public function setGooglePlaceId(?string $googlePlaceId): static
    {
        $this->googlePlaceId = $googlePlaceId;
        return $this;
    }

    // Méthodes utilitaires existantes

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(User $admin, ?string $response = null): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewedBy = $admin;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->adminResponse = $response;
    }

    public function reject(User $admin, ?string $response = null): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewedBy = $admin;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->adminResponse = $response;
    }

    public function getRoleDisplayName(): string
    {
        return match($this->requestedRole) {
            self::ROLE_ORGANIZER => 'Organisateur',
            self::ROLE_SHOP => 'Boutique',
            default => 'Inconnu'
        };
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvée',
            self::STATUS_REJECTED => 'Refusée',
            default => 'Inconnu'
        };
    }

    // 🆕 NOUVELLES MÉTHODES UTILITAIRES

    /**
     * Vérifie si les données de vérification sont récentes (< 7 jours)
     */
    public function hasRecentVerification(): bool
    {
        if (!$this->verificationDate) {
            return false;
        }

        $weekAgo = new \DateTimeImmutable('-7 days');
        return $this->verificationDate > $weekAgo;
    }

    /**
     * Retourne le niveau de confiance basé sur le score
     */
    public function getConfidenceLevel(): string
    {
        if ($this->verificationScore === null) {
            return 'unknown';
        }

        return match(true) {
            $this->verificationScore >= 80 => 'high',
            $this->verificationScore >= 60 => 'medium',
            $this->verificationScore >= 40 => 'low',
            default => 'very_low'
        };
    }

    /**
     * Vérifie si une nouvelle vérification est nécessaire
     */
    public function needsVerification(): bool
    {
        return !$this->hasRecentVerification() && $this->requestedRole === self::ROLE_SHOP;
    }

    /**
     * Validation spécifique des données boutique (mise à jour avec SIRET Luhn)
     */
    public function validateShopData(): array
    {
        $errors = [];
        
        if ($this->requestedRole === self::ROLE_SHOP) {
            if (!$this->shopName) {
                $errors['shopName'] = 'Le nom de la boutique est obligatoire';
            }
            
            if (!$this->siretNumber) {
                $errors['siretNumber'] = 'Le numéro SIRET est obligatoire';
            } else {
                // Validation SIRET améliorée
                $cleanSiret = str_replace([' ', '-', '.'], '', $this->siretNumber);
                
                if (!preg_match('/^\d{14}$/', $cleanSiret)) {
                    $errors['siretNumber'] = 'Le SIRET doit contenir exactement 14 chiffres';
                } elseif (!$this->validateSiretLuhn($cleanSiret)) {
                    $errors['siretNumber'] = 'Le numéro SIRET est invalide (clé de contrôle incorrecte)';
                }
            }
            
            if (!$this->shopPhone) {
                $errors['shopPhone'] = 'Le téléphone est obligatoire';
            } elseif (!preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $this->shopPhone)) {
                $errors['shopPhone'] = 'Numéro de téléphone français invalide';
            }
            
            if (!$this->shopAddress) {
                $errors['shopAddress'] = 'L\'adresse de la boutique est obligatoire';
            }
        }
        
        return $errors;
    }

    /**
     * Validation SIRET avec algorithme de Luhn officiel français
     */
    private function validateSiretLuhn(string $siret): bool
    {
        if (strlen($siret) !== 14) {
            return false;
        }

        $sum = 0;
        $length = strlen($siret);

        // Parcourir de droite à gauche
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $siret[$i];
            $position = $length - $i; // Position depuis la droite (1, 2, 3, ...)
            
            // Multiplier par 2 les chiffres en position PAIRE (2, 4, 6, ...) depuis la droite
            if ($position % 2 === 0) {
                $digit *= 2;
                
                // Si résultat > 9, soustraire 9 (équivalent à additionner les chiffres)
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}