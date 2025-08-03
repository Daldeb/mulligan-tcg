<?php

namespace App\Entity;

use App\Repository\TournamentMatchRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
#[ORM\Table(name: 'tournament_match')]
class TournamentMatch
{
    // Status du match
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_BYE = 'BYE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le tournoi est requis')]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(targetEntity: TournamentRound::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(name: 'round_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le round est requis')]
    private ?TournamentRound $round = null;

    #[ORM\ManyToOne(targetEntity: EventRegistration::class)]
    #[ORM\JoinColumn(name: 'player1_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le joueur 1 est requis')]
    private ?EventRegistration $player1 = null;

    #[ORM\ManyToOne(targetEntity: EventRegistration::class)]
    #[ORM\JoinColumn(name: 'player2_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?EventRegistration $player2 = null;

    #[ORM\ManyToOne(targetEntity: EventRegistration::class)]
    #[ORM\JoinColumn(name: 'winner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EventRegistration $winner = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FINISHED,
        self::STATUS_CANCELLED,
        self::STATUS_BYE
    ])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: 'Le numéro de table doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $tableNumber = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 10,
        notInRangeMessage: 'Le score doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $player1Score = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 10,
        notInRangeMessage: 'Le score doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $player2Score = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $gameResults = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 300,
        notInRangeMessage: 'La durée doit être entre {{ min }} et {{ max }} minutes'
    )]
    private ?int $duration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $additionalData = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->gameResults = [];
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

    public function getRound(): ?TournamentRound
    {
        return $this->round;
    }

    public function setRound(?TournamentRound $round): static
    {
        $this->round = $round;
        return $this;
    }

    public function getPlayer1(): ?EventRegistration
    {
        return $this->player1;
    }

    public function setPlayer1(?EventRegistration $player1): static
    {
        $this->player1 = $player1;
        return $this;
    }

    public function getPlayer2(): ?EventRegistration
    {
        return $this->player2;
    }

    public function setPlayer2(?EventRegistration $player2): static
    {
        $this->player2 = $player2;
        
        // Si player2 est null, c'est un bye
        if ($player2 === null) {
            $this->status = self::STATUS_BYE;
            $this->winner = $this->player1;
            $this->player1Score = 2;
            $this->player2Score = 0;
        }
        
        return $this;
    }

    public function getWinner(): ?EventRegistration
    {
        return $this->winner;
    }

    public function setWinner(?EventRegistration $winner): static
    {
        $this->winner = $winner;
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
        if ($status === self::STATUS_IN_PROGRESS && !$this->startedAt) {
            $this->startedAt = new \DateTimeImmutable();
        } elseif ($status === self::STATUS_FINISHED && !$this->finishedAt) {
            $this->finishedAt = new \DateTimeImmutable();
            $this->calculateDuration();
        }
        
        return $this;
    }

    public function getTableNumber(): ?int
    {
        return $this->tableNumber;
    }

    public function setTableNumber(?int $tableNumber): static
    {
        $this->tableNumber = $tableNumber;
        return $this;
    }

    public function getPlayer1Score(): ?int
    {
        return $this->player1Score;
    }

    public function setPlayer1Score(?int $player1Score): static
    {
        $this->player1Score = $player1Score;
        return $this;
    }

    public function getPlayer2Score(): ?int
    {
        return $this->player2Score;
    }

    public function setPlayer2Score(?int $player2Score): static
    {
        $this->player2Score = $player2Score;
        return $this;
    }

    public function getGameResults(): ?array
    {
        return $this->gameResults;
    }

    public function setGameResults(?array $gameResults): static
    {
        $this->gameResults = $gameResults ?? [];
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
        $this->calculateDuration();
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
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

    // ============= MÉTHODES UTILITAIRES =============

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isBye(): bool
    {
        return $this->status === self::STATUS_BYE;
    }

    public function hasBye(): bool
    {
        return $this->player2 === null;
    }

    public function canStart(): bool
    {
        return $this->isPending() && !$this->hasBye();
    }

    public function canFinish(): bool
    {
        return $this->isInProgress() || $this->isBye();
    }

    public function start(): static
    {
        if (!$this->canStart()) {
            throw new \LogicException('Le match ne peut pas être démarré dans son état actuel');
        }
        
        $this->setStatus(self::STATUS_IN_PROGRESS);
        return $this;
    }

    public function finish(int $player1Score, int $player2Score): static
    {
        if (!$this->canFinish()) {
            throw new \LogicException('Le match ne peut pas être terminé dans son état actuel');
        }
        
        $this->player1Score = $player1Score;
        $this->player2Score = $player2Score;
        
        // Déterminer le gagnant
        if ($player1Score > $player2Score) {
            $this->winner = $this->player1;
        } elseif ($player2Score > $player1Score) {
            $this->winner = $this->player2;
        }
        // Si égalité, winner reste null (match nul)
        
        $this->setStatus(self::STATUS_FINISHED);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus(self::STATUS_CANCELLED);
        return $this;
    }

    public function getScoreDisplay(): string
    {
        if ($this->player1Score === null || $this->player2Score === null) {
            return '-';
        }
        
        return sprintf('%d-%d', $this->player1Score, $this->player2Score);
    }

    public function getOpponent(EventRegistration $player): ?EventRegistration
    {
        if ($player === $this->player1) {
            return $this->player2;
        } elseif ($player === $this->player2) {
            return $this->player1;
        }
        
        return null;
    }

    public function isPlayer(EventRegistration $player): bool
    {
        return $player === $this->player1 || $player === $this->player2;
    }

    public function getPlayerScore(EventRegistration $player): ?int
    {
        if ($player === $this->player1) {
            return $this->player1Score;
        } elseif ($player === $this->player2) {
            return $this->player2Score;
        }
        
        return null;
    }

    public function didPlayerWin(EventRegistration $player): bool
    {
        return $this->winner === $player;
    }

    public function isDraw(): bool
    {
        return $this->isFinished() && $this->winner === null;
    }

    public function addGameResult(string $winner, int $duration): static
    {
        $games = $this->gameResults ?? [];
        $games[] = [
            'winner' => $winner,
            'duration' => $duration,
            'timestamp' => (new \DateTimeImmutable())->format('c')
        ];
        $this->gameResults = $games;
        return $this;
    }

    public function getGamesCount(): int
    {
        return count($this->gameResults ?? []);
    }

    public function calculateDuration(): void
    {
        if ($this->startedAt && $this->finishedAt) {
            $interval = $this->startedAt->diff($this->finishedAt);
            $this->duration = ($interval->h * 60) + $interval->i;
        }
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_FINISHED => 'Terminé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_BYE => 'Bye',
            default => 'Inconnu'
        };
    }

    public function getMatchDisplayName(): string
    {
        $player1Name = $this->player1?->getUser()?->getPseudo() ?? 'Joueur 1';
        
        if ($this->hasBye()) {
            return $player1Name . ' (Bye)';
        }
        
        $player2Name = $this->player2?->getUser()?->getPseudo() ?? 'Joueur 2';
        return $player1Name . ' vs ' . $player2Name;
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
}