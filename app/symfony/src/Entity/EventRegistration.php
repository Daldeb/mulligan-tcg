<?php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
#[ORM\Table(name: 'event_registration')]
#[UniqueEntity(fields: ['event', 'user'], message: 'Vous êtes déjà inscrit à cet événement')]
class EventRegistration
{
    // Status de l'inscription
    public const STATUS_REGISTERED = 'REGISTERED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_NO_SHOW = 'NO_SHOW';
    public const STATUS_DISQUALIFIED = 'DISQUALIFIED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'registrations')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'événement est requis')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'utilisateur est requis')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_REGISTERED,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
        self::STATUS_DISQUALIFIED
    ])]
    private string $status = self::STATUS_REGISTERED;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $additionalData = null;

    // Données spécifiques aux tournois
    #[ORM\ManyToOne(targetEntity: Deck::class)]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Deck $deck = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deckList = null;

    #[ORM\Column(type: 'boolean')]
    private bool $deckListSubmitted = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deckListSubmittedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $checkedIn = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkedInAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $seedNumber = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $finalRanking = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tournamentStats = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTimeImmutable();
        $this->additionalData = [];
        $this->tournamentStats = [
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'match_points' => 0,
            'game_points' => 0,
            'opponent_match_win_percentage' => 0.0,
            'game_win_percentage' => 0.0,
            'opponent_game_win_percentage' => 0.0
        ];
    }

    // ============= GETTERS & SETTERS =============

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        // Met à jour automatiquement les timestamps
        if ($status === self::STATUS_CONFIRMED && !$this->confirmedAt) {
            $this->confirmedAt = new \DateTimeImmutable();
        } elseif ($status === self::STATUS_CANCELLED && !$this->cancelledAt) {
            $this->cancelledAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
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

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;
        return $this;
    }

    public function getDeckList(): ?string
    {
        return $this->deckList;
    }

    public function setDeckList(?string $deckList): static
    {
        $this->deckList = $deckList;
        return $this;
    }

    public function isDeckListSubmitted(): bool
    {
        return $this->deckListSubmitted;
    }

    public function setDeckListSubmitted(bool $deckListSubmitted): static
    {
        $this->deckListSubmitted = $deckListSubmitted;
        
        if ($deckListSubmitted && !$this->deckListSubmittedAt) {
            $this->deckListSubmittedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getDeckListSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->deckListSubmittedAt;
    }

    public function setDeckListSubmittedAt(?\DateTimeImmutable $deckListSubmittedAt): static
    {
        $this->deckListSubmittedAt = $deckListSubmittedAt;
        return $this;
    }

    public function isCheckedIn(): bool
    {
        return $this->checkedIn;
    }

    public function setCheckedIn(bool $checkedIn): static
    {
        $this->checkedIn = $checkedIn;
        
        if ($checkedIn && !$this->checkedInAt) {
            $this->checkedInAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getCheckedInAt(): ?\DateTimeImmutable
    {
        return $this->checkedInAt;
    }

    public function setCheckedInAt(?\DateTimeImmutable $checkedInAt): static
    {
        $this->checkedInAt = $checkedInAt;
        return $this;
    }

    public function getSeedNumber(): ?int
    {
        return $this->seedNumber;
    }

    public function setSeedNumber(?int $seedNumber): static
    {
        $this->seedNumber = $seedNumber;
        return $this;
    }

    public function getFinalRanking(): ?int
    {
        return $this->finalRanking;
    }

    public function setFinalRanking(?int $finalRanking): static
    {
        $this->finalRanking = $finalRanking;
        return $this;
    }

    public function getTournamentStats(): ?array
    {
        return $this->tournamentStats;
    }

    public function setTournamentStats(?array $tournamentStats): static
    {
        $this->tournamentStats = $tournamentStats ?? [];
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES =============

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_REGISTERED,
            self::STATUS_CONFIRMED
        ], true);
    }

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isNoShow(): bool
    {
        return $this->status === self::STATUS_NO_SHOW;
    }

    public function isDisqualified(): bool
    {
        return $this->status === self::STATUS_DISQUALIFIED;
    }

    public function confirm(): static
    {
        $this->setStatus(self::STATUS_CONFIRMED);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus(self::STATUS_CANCELLED);
        return $this;
    }

    public function markAsNoShow(): static
    {
        $this->setStatus(self::STATUS_NO_SHOW);
        return $this;
    }

    public function disqualify(): static
    {
        $this->setStatus(self::STATUS_DISQUALIFIED);
        return $this;
    }

    public function checkIn(): static
    {
        $this->setCheckedIn(true);
        if ($this->status === self::STATUS_REGISTERED) {
            $this->confirm();
        }
        return $this;
    }

    public function submitDeckList(string $deckList): static
    {
        $this->deckList = $deckList;
        $this->setDeckListSubmitted(true);
        return $this;
    }

    public function canCheckIn(): bool
    {
        return $this->isActive() && !$this->checkedIn;
    }

    public function needsDeckList(): bool
    {
        if (!$this->event instanceof Tournament) {
            return false;
        }
        
        return $this->event->isRequireDecklist() && !$this->deckListSubmitted;
    }

    public function canSubmitDeckList(): bool
    {
        if (!$this->event instanceof Tournament) {
            return false;
        }
        
        return $this->event->isAllowDecklist() && $this->isActive();
    }

    public function getMatchRecord(): string
    {
        $stats = $this->tournamentStats ?? [];
        $wins = $stats['wins'] ?? 0;
        $losses = $stats['losses'] ?? 0;
        $draws = $stats['draws'] ?? 0;
        
        return sprintf('%d-%d-%d', $wins, $losses, $draws);
    }

    public function getMatchPoints(): int
    {
        $stats = $this->tournamentStats ?? [];
        return $stats['match_points'] ?? 0;
    }

    public function getGamePoints(): int
    {
        $stats = $this->tournamentStats ?? [];
        return $stats['game_points'] ?? 0;
    }

    public function getWinPercentage(): float
    {
        $stats = $this->tournamentStats ?? [];
        $wins = $stats['wins'] ?? 0;
        $total = ($stats['wins'] ?? 0) + ($stats['losses'] ?? 0) + ($stats['draws'] ?? 0);
        
        if ($total === 0) {
            return 0.0;
        }
        
        return ($wins / $total) * 100;
    }

    public function updateTournamentStats(array $newStats): static
    {
        $this->tournamentStats = array_merge($this->tournamentStats ?? [], $newStats);
        return $this;
    }

    public function addMatchResult(bool $won, bool $draw = false): static
    {
        $stats = $this->tournamentStats ?? [];
        
        if ($draw) {
            $stats['draws'] = ($stats['draws'] ?? 0) + 1;
            $stats['match_points'] = ($stats['match_points'] ?? 0) + 1;
        } elseif ($won) {
            $stats['wins'] = ($stats['wins'] ?? 0) + 1;
            $stats['match_points'] = ($stats['match_points'] ?? 0) + 3;
        } else {
            $stats['losses'] = ($stats['losses'] ?? 0) + 1;
        }
        
        $this->tournamentStats = $stats;
        return $this;
    }

    public function addGamePoints(int $points): static
    {
        $stats = $this->tournamentStats ?? [];
        $stats['game_points'] = ($stats['game_points'] ?? 0) + $points;
        $this->tournamentStats = $stats;
        return $this;
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_REGISTERED => 'Inscrit',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_NO_SHOW => 'Absent',
            self::STATUS_DISQUALIFIED => 'Disqualifié',
            default => 'Inconnu'
        };
    }

    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return $this->additionalData[$key] ?? $default;
    }

    public function setDataValue(string $key, mixed $value): static
    {
        $data = $this->additionalData ?? [];
        $data[$key] = $value;
        $this->additionalData = $data;
        return $this;
    }

    public function removeDataValue(string $key): static
    {
        $data = $this->additionalData ?? [];
        unset($data[$key]);
        $this->additionalData = $data;
        return $this;
    }

    public function getTournamentStatValue(string $key, mixed $default = null): mixed
    {
        return $this->tournamentStats[$key] ?? $default;
    }

    public function setTournamentStatValue(string $key, mixed $value): static
    {
        $stats = $this->tournamentStats ?? [];
        $stats[$key] = $value;
        $this->tournamentStats = $stats;
        return $this;
    }
}