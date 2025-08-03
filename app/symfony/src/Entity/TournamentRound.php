<?php

namespace App\Entity;

use App\Repository\TournamentRoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentRoundRepository::class)]
#[ORM\Table(name: 'tournament_round')]
class TournamentRound
{
    // Status du round
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_CANCELLED = 'CANCELLED';

    // Types de round
    public const TYPE_SWISS = 'SWISS';
    public const TYPE_TOP_CUT = 'TOP_CUT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'rounds')]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le tournoi est requis')]
    private ?Tournament $tournament = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(
        min: 1,
        max: 50,
        notInRangeMessage: 'Le numéro de round doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $roundNumber = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_SWISS, self::TYPE_TOP_CUT])]
    private ?string $roundType = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_FINISHED,
        self::STATUS_CANCELLED
    ])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 10,
        max: 300,
        notInRangeMessage: 'La limite de temps doit être entre {{ min }} et {{ max }} minutes'
    )]
    private ?int $timeLimit = null;

    #[ORM\Column(type: 'boolean')]
    private bool $pairingsGenerated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $pairingsGeneratedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $allMatchesFinished = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $pairingsData = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $standings = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $additionalData = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, TournamentMatch>
     */
    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'round', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['tableNumber' => 'ASC', 'id' => 'ASC'])]
    private Collection $matches;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->matches = new ArrayCollection();
        $this->pairingsData = [];
        $this->standings = [];
        $this->additionalData = [];
    }

    // ============= GETTERS & SETTERS =============

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function getRoundNumber(): ?int
    {
        return $this->roundNumber;
    }

    public function setRoundNumber(int $roundNumber): static
    {
        $this->roundNumber = $roundNumber;
        return $this;
    }

    public function getRoundType(): ?string
    {
        return $this->roundType;
    }

    public function setRoundType(string $roundType): static
    {
        $this->roundType = $roundType;
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
        
        // Gestion automatique des timestamps
        if ($status === self::STATUS_ACTIVE && !$this->startedAt) {
            $this->startedAt = new \DateTimeImmutable();
        } elseif ($status === self::STATUS_FINISHED && !$this->finishedAt) {
            $this->finishedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }

    public function isPairingsGenerated(): bool
    {
        return $this->pairingsGenerated;
    }

    public function setPairingsGenerated(bool $pairingsGenerated): static
    {
        $this->pairingsGenerated = $pairingsGenerated;
        
        if ($pairingsGenerated && !$this->pairingsGeneratedAt) {
            $this->pairingsGeneratedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getPairingsGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->pairingsGeneratedAt;
    }

    public function setPairingsGeneratedAt(?\DateTimeImmutable $pairingsGeneratedAt): static
    {
        $this->pairingsGeneratedAt = $pairingsGeneratedAt;
        return $this;
    }

    public function isAllMatchesFinished(): bool
    {
        return $this->allMatchesFinished;
    }

    public function setAllMatchesFinished(bool $allMatchesFinished): static
    {
        $this->allMatchesFinished = $allMatchesFinished;
        return $this;
    }

    public function getPairingsData(): ?array
    {
        return $this->pairingsData;
    }

    public function setPairingsData(?array $pairingsData): static
    {
        $this->pairingsData = $pairingsData ?? [];
        return $this;
    }

    public function getStandings(): ?array
    {
        return $this->standings;
    }

    public function setStandings(?array $standings): static
    {
        $this->standings = $standings ?? [];
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

    /**
     * @return Collection<int, TournamentMatch>
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(TournamentMatch $match): static
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setRound($this);
        }
        return $this;
    }

    public function removeMatch(TournamentMatch $match): static
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getRound() === $this) {
                $match->setRound(null);
            }
        }
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES =============

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSwiss(): bool
    {
        return $this->roundType === self::TYPE_SWISS;
    }

    public function isTopCut(): bool
    {
        return $this->roundType === self::TYPE_TOP_CUT;
    }

    public function canStart(): bool
    {
        return $this->isPending() && $this->pairingsGenerated && $this->matches->count() > 0;
    }

    public function canGeneratePairings(): bool
    {
        return $this->isPending() && !$this->pairingsGenerated;
    }

    public function start(): static
    {
        if (!$this->canStart()) {
            throw new \LogicException('Le round ne peut pas être démarré dans son état actuel');
        }
        
        $this->setStatus(self::STATUS_ACTIVE);
        return $this;
    }

    public function finish(): static
    {
        if (!$this->checkAllMatchesFinished()) {
            throw new \LogicException('Tous les matchs doivent être terminés avant de clôturer le round');
        }
        
        $this->allMatchesFinished = true;
        $this->setStatus(self::STATUS_FINISHED);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus(self::STATUS_CANCELLED);
        return $this;
    }

    public function checkAllMatchesFinished(): bool
    {
        foreach ($this->matches as $match) {
            if (!$match->isFinished() && !$match->isCancelled()) {
                return false;
            }
        }
        return true;
    }

    public function updateMatchesStatus(): static
    {
        $allFinished = $this->checkAllMatchesFinished();
        $this->allMatchesFinished = $allFinished;
        
        if ($allFinished && $this->isActive()) {
            $this->finish();
        }
        
        return $this;
    }

    public function getMatchesCount(): int
    {
        return $this->matches->count();
    }

    public function getFinishedMatchesCount(): int
    {
        return $this->matches->filter(
            fn(TournamentMatch $match) => $match->isFinished()
        )->count();
    }

    public function getPendingMatchesCount(): int
    {
        return $this->matches->filter(
            fn(TournamentMatch $match) => $match->isPending()
        )->count();
    }

    public function getInProgressMatchesCount(): int
    {
        return $this->matches->filter(
            fn(TournamentMatch $match) => $match->isInProgress()
        )->count();
    }

    public function getProgress(): float
    {
        $total = $this->getMatchesCount();
        if ($total === 0) {
            return 0.0;
        }
        
        $finished = $this->getFinishedMatchesCount();
        return ($finished / $total) * 100;
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->startedAt) {
            return null;
        }
        
        $end = $this->finishedAt ?? new \DateTimeImmutable();
        return $this->startedAt->diff($end);
    }

    public function isOvertime(): bool
    {
        if (!$this->timeLimit || !$this->startedAt || $this->isFinished()) {
            return false;
        }
        
        $elapsed = $this->startedAt->diff(new \DateTimeImmutable());
        $elapsedMinutes = ($elapsed->h * 60) + $elapsed->i;
        
        return $elapsedMinutes > $this->timeLimit;
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->timeLimit || !$this->startedAt || $this->isFinished()) {
            return null;
        }
        
        $elapsed = $this->startedAt->diff(new \DateTimeImmutable());
        $elapsedMinutes = ($elapsed->h * 60) + $elapsed->i;
        
        return max(0, $this->timeLimit - $elapsedMinutes);
    }

    public function generatePairings(array $players): static
    {
        if (!$this->canGeneratePairings()) {
            throw new \LogicException('Les pairings ne peuvent pas être générés dans l\'état actuel');
        }
        
        // La logique de génération sera implémentée dans un service
        $this->setPairingsGenerated(true);
        return $this;
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACTIVE => 'En cours',
            self::STATUS_FINISHED => 'Terminé',
            self::STATUS_CANCELLED => 'Annulé',
            default => 'Inconnu'
        };
    }

    public function getRoundTypeDisplayName(): string
    {
        return match($this->roundType) {
            self::TYPE_SWISS => 'Swiss',
            self::TYPE_TOP_CUT => 'Top Cut',
            default => 'Inconnu'
        };
    }

    public function getRoundDisplayName(): string
    {
        $typeName = $this->getRoundTypeDisplayName();
        return sprintf('%s - Round %d', $typeName, $this->roundNumber);
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
}