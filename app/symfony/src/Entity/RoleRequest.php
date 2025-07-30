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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

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

    /**
     * Validation spécifique des données boutique
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
            } elseif (!preg_match('/^\d{14}$/', str_replace(' ', '', $this->siretNumber))) {
                $errors['siretNumber'] = 'Le SIRET doit contenir exactement 14 chiffres';
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
}