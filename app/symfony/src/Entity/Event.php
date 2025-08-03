<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator_type', type: 'string')]
#[ORM\DiscriminatorMap([
    'AVANT_PREMIERE' => Event::class,
    'TOURNOI' => Tournament::class,
    'RENCONTRE' => Event::class,
    'GENERIQUE' => Event::class
])]
class Event
{
    // Types d'événements
    public const TYPE_AVANT_PREMIERE = 'AVANT_PREMIERE';
    public const TYPE_TOURNOI = 'TOURNOI';
    public const TYPE_RENCONTRE = 'RENCONTRE';
    public const TYPE_GENERIQUE = 'GENERIQUE';

    // Status de l'événement
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_FINISHED = 'FINISHED';

    // Visibilité (contrôlée par admin)
    public const VISIBILITY_VISIBLE = 'VISIBLE';
    public const VISIBILITY_HIDDEN = 'HIDDEN';

    // Types d'organisateurs
    public const ORGANIZER_USER = 'USER';
    public const ORGANIZER_SHOP = 'SHOP';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le titre doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::TYPE_AVANT_PREMIERE,
        self::TYPE_TOURNOI,
        self::TYPE_RENCONTRE,
        self::TYPE_GENERIQUE
    ])]
    private ?string $eventType = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FINISHED
    ])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::VISIBILITY_VISIBLE, self::VISIBILITY_HIDDEN])]
    private string $visibility = self::VISIBILITY_HIDDEN;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'La date de début est requise')]
    #[Assert\GreaterThan('now', message: 'La date de début doit être dans le futur')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Assert\LessThan(propertyPath: 'startDate', message: 'La date limite d\'inscription doit être avant le début de l\'événement')]
    private ?\DateTimeImmutable $registrationDeadline = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 1000,
        notInRangeMessage: 'Le nombre maximum de participants doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'integer')]
    private int $currentParticipants = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isOnline = false;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Address $address = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::ORGANIZER_USER, self::ORGANIZER_SHOP])]
    private ?string $organizerType = null;

    #[ORM\Column(type: 'integer')]
    private ?int $organizerId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le créateur est requis')]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix d\'entrée doit être positif')]
    private ?string $entryFee = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rules = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $prizes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL de stream n\'est pas valide')]
    private ?string $streamUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $additionalData = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewed_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reviewComment = null;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\ManyToMany(targetEntity: Game::class)]
    #[ORM\JoinTable(name: 'event_games')]
    private Collection $games;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(targetEntity: EventRegistration::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['registeredAt' => 'ASC'])]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->games = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->tags = [];
        $this->additionalData = [];
    }

    // ============= GETTERS & SETTERS =============

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->updateTimestamp();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updateTimestamp();
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updateTimestamp();
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        $this->updateTimestamp();
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        $this->updateTimestamp();
        return $this;
    }

    public function getRegistrationDeadline(): ?\DateTimeImmutable
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(?\DateTimeImmutable $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;
        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function getCurrentParticipants(): int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(int $currentParticipants): static
    {
        $this->currentParticipants = $currentParticipants;
        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;
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

    public function getOrganizerType(): ?string
    {
        return $this->organizerType;
    }

    public function setOrganizerType(string $organizerType): static
    {
        $this->organizerType = $organizerType;
        return $this;
    }

    public function getOrganizerId(): ?int
    {
        return $this->organizerId;
    }

    public function setOrganizerId(int $organizerId): static
    {
        $this->organizerId = $organizerId;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags ?? [];
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getEntryFee(): ?string
    {
        return $this->entryFee;
    }

    public function setEntryFee(?string $entryFee): static
    {
        $this->entryFee = $entryFee;
        return $this;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    public function setRules(?string $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function getPrizes(): ?string
    {
        return $this->prizes;
    }

    public function setPrizes(?string $prizes): static
    {
        $this->prizes = $prizes;
        return $this;
    }

    public function getStreamUrl(): ?string
    {
        return $this->streamUrl;
    }

    public function setStreamUrl(?string $streamUrl): static
    {
        $this->streamUrl = $streamUrl;
        return $this;
    }

    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(?array $additionalData): static
    {
        $this->additionalData = $additionalData ?? [];
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

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
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

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(?string $reviewComment): static
    {
        $this->reviewComment = $reviewComment;
        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
        }
        return $this;
    }

    public function removeGame(Game $game): static
    {
        $this->games->removeElement($game);
        return $this;
    }

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(EventRegistration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
            $this->currentParticipants = $this->registrations->count();
        }
        return $this;
    }

    public function removeRegistration(EventRegistration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
            $this->currentParticipants = $this->registrations->count();
        }
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES =============

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_VISIBLE;
    }

    public function isHidden(): bool
    {
        return $this->visibility === self::VISIBILITY_HIDDEN;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function canRegister(): bool
    {
        if (!$this->isApproved() || !$this->isVisible()) {
            return false;
        }

        if ($this->registrationDeadline && $this->registrationDeadline < new \DateTimeImmutable()) {
            return false;
        }

        if ($this->maxParticipants && $this->currentParticipants >= $this->maxParticipants) {
            return false;
        }

        return !$this->isInProgress() && !$this->isFinished();
    }

    public function isFull(): bool
    {
        return $this->maxParticipants && $this->currentParticipants >= $this->maxParticipants;
    }

    public function getRemainingSlots(): ?int
    {
        if (!$this->maxParticipants) {
            return null;
        }
        return max(0, $this->maxParticipants - $this->currentParticipants);
    }

    public function getOrganizerEntity(): User|Shop|null
    {
        if (!$this->organizerType || !$this->organizerId) {
            return null;
        }

        // Cette méthode sera complétée avec un service pour récupérer l'entité
        return null;
    }

    public function getOrganizerName(): string
    {
        if ($this->organizerType === self::ORGANIZER_USER) {
            return $this->createdBy?->getDisplayName() ?? 'Utilisateur';
        }
        
        // Pour Shop, il faudra récupérer via service
        return 'Organisateur';
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    public function addTag(string $tag): static
    {
        if (!$this->hasTag($tag)) {
            $tags = $this->tags ?? [];
            $tags[] = $tag;
            $this->tags = $tags;
        }
        return $this;
    }

    public function removeTag(string $tag): static
    {
        $this->tags = array_values(array_filter(
            $this->tags ?? [],
            fn(string $t) => $t !== $tag
        ));
        return $this;
    }

    public function approve(User $admin, ?string $comment = null): static
    {
        $this->status = self::STATUS_APPROVED;
        $this->visibility = self::VISIBILITY_VISIBLE;
        $this->reviewedBy = $admin;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewComment = $comment;
        return $this;
    }

    public function reject(User $admin, ?string $comment = null): static
    {
        $this->status = self::STATUS_REJECTED;
        $this->visibility = self::VISIBILITY_HIDDEN;
        $this->reviewedBy = $admin;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewComment = $comment;
        return $this;
    }

    public function hide(User $admin, ?string $comment = null): static
    {
        $this->visibility = self::VISIBILITY_HIDDEN;
        $this->reviewedBy = $admin;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewComment = $comment;
        return $this;
    }

    public function submitForReview(): static
    {
        $this->status = self::STATUS_PENDING_REVIEW;
        $this->updateTimestamp();
        return $this;
    }

    public function start(): static
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->updateTimestamp();
        return $this;
    }

    public function finish(): static
    {
        $this->status = self::STATUS_FINISHED;
        $this->updateTimestamp();
        return $this;
    }

    public function cancel(): static
    {
        $this->status = self::STATUS_CANCELLED;
        $this->updateTimestamp();
        return $this;
    }

    public function getEventTypeDisplayName(): string
    {
        return match($this->eventType) {
            self::TYPE_AVANT_PREMIERE => 'Avant-Première',
            self::TYPE_TOURNOI => 'Tournoi',
            self::TYPE_RENCONTRE => 'Rencontre',
            self::TYPE_GENERIQUE => 'Événement',
            default => 'Inconnu'
        };
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PENDING_REVIEW => 'En attente de validation',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Refusé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_FINISHED => 'Terminé',
            default => 'Inconnu'
        };
    }
}