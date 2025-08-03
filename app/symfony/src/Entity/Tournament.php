<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournament')]
class Tournament extends Event
{
    // Formats de tournoi
    public const FORMAT_SWISS = 'SWISS';
    public const FORMAT_ELIMINATION = 'ELIMINATION';
    public const FORMAT_ROUND_ROBIN = 'ROUND_ROBIN';

    // Phases du tournoi
    public const PHASE_REGISTRATION = 'REGISTRATION';
    public const PHASE_SWISS = 'SWISS';
    public const PHASE_TOP_CUT = 'TOP_CUT';
    public const PHASE_FINISHED = 'FINISHED';

    #[ORM\ManyToOne(targetEntity: GameFormat::class)]
    #[ORM\JoinColumn(name: 'game_format_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Le format de jeu est requis pour un tournoi')]
    private ?GameFormat $gameFormat = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::FORMAT_SWISS,
        self::FORMAT_ELIMINATION,
        self::FORMAT_ROUND_ROBIN
    ])]
    private ?string $tournamentFormat = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::PHASE_REGISTRATION,
        self::PHASE_SWISS,
        self::PHASE_TOP_CUT,
        self::PHASE_FINISHED
    ])]
    private string $currentPhase = self::PHASE_REGISTRATION;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 50,
        notInRangeMessage: 'Le nombre de rounds doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $swissRounds = null;

    #[ORM\Column(type: 'integer')]
    private int $currentRound = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 2,
        max: 64,
        notInRangeMessage: 'Le top cut doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $topCutSize = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(
        min: 10,
        max: 180,
        notInRangeMessage: 'Le timer par match doit être entre {{ min }} et {{ max }} minutes'
    )]
    private int $matchTimer = 50;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(
        min: 5,
        max: 60,
        notInRangeMessage: 'Le timer de pause doit être entre {{ min }} et {{ max }} minutes'
    )]
    private ?int $breakTimer = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bracketData = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $standings = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $pairings = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prize pool doit être positif')]
    private ?string $prizePool = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $prizeDistribution = null;

    #[ORM\Column(type: 'boolean')]
    private bool $allowDecklist = false;

    #[ORM\Column(type: 'boolean')]
    private bool $requireDecklist = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tournamentConfig = null;

    /**
     * @var Collection<int, TournamentRound>
     */
    #[ORM\OneToMany(targetEntity: TournamentRound::class, mappedBy: 'tournament', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['roundNumber' => 'ASC'])]
    private Collection $rounds;

    /**
     * @var Collection<int, TournamentMatch>
     */
    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'tournament', cascade: ['persist', 'remove'])]
    private Collection $matches;

    public function __construct()
    {
        parent::__construct();
        $this->setEventType(self::TYPE_TOURNOI);
        $this->rounds = new ArrayCollection();
        $this->matches = new ArrayCollection();
        $this->bracketData = [];
        $this->standings = [];
        $this->pairings = [];
        $this->prizeDistribution = [];
        $this->tournamentConfig = [];
    }

    // ============= GETTERS & SETTERS =============

    public function getGameFormat(): ?GameFormat
    {
        return $this->gameFormat;
    }

    public function setGameFormat(?GameFormat $gameFormat): static
    {
        $this->gameFormat = $gameFormat;
        
        // Auto-associer le jeu si pas déjà fait
        if ($gameFormat && $gameFormat->getGame()) {
            $this->addGame($gameFormat->getGame());
        }
        
        return $this;
    }

    public function getTournamentFormat(): ?string
    {
        return $this->tournamentFormat;
    }

    public function setTournamentFormat(string $tournamentFormat): static
    {
        $this->tournamentFormat = $tournamentFormat;
        $this->updateTimestamp();
        return $this;
    }

    public function getCurrentPhase(): string
    {
        return $this->currentPhase;
    }

    public function setCurrentPhase(string $currentPhase): static
    {
        $this->currentPhase = $currentPhase;
        $this->updateTimestamp();
        return $this;
    }

    public function getSwissRounds(): ?int
    {
        return $this->swissRounds;
    }

    public function setSwissRounds(?int $swissRounds): static
    {
        $this->swissRounds = $swissRounds;
        return $this;
    }

    public function getCurrentRound(): int
    {
        return $this->currentRound;
    }

    public function setCurrentRound(int $currentRound): static
    {
        $this->currentRound = $currentRound;
        return $this;
    }

    public function getTopCutSize(): ?int
    {
        return $this->topCutSize;
    }

    public function setTopCutSize(?int $topCutSize): static
    {
        $this->topCutSize = $topCutSize;
        return $this;
    }

    public function getMatchTimer(): int
    {
        return $this->matchTimer;
    }

    public function setMatchTimer(int $matchTimer): static
    {
        $this->matchTimer = $matchTimer;
        return $this;
    }

    public function getBreakTimer(): ?int
    {
        return $this->breakTimer;
    }

    public function setBreakTimer(?int $breakTimer): static
    {
        $this->breakTimer = $breakTimer;
        return $this;
    }

    public function getBracketData(): ?array
    {
        return $this->bracketData;
    }

    public function setBracketData(?array $bracketData): static
    {
        $this->bracketData = $bracketData ?? [];
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

    public function getPairings(): ?array
    {
        return $this->pairings;
    }

    public function setPairings(?array $pairings): static
    {
        $this->pairings = $pairings ?? [];
        return $this;
    }

    public function getPrizePool(): ?string
    {
        return $this->prizePool;
    }

    public function setPrizePool(?string $prizePool): static
    {
        $this->prizePool = $prizePool;
        return $this;
    }

    public function getPrizeDistribution(): ?array
    {
        return $this->prizeDistribution;
    }

    public function setPrizeDistribution(?array $prizeDistribution): static
    {
        $this->prizeDistribution = $prizeDistribution ?? [];
        return $this;
    }

    public function isAllowDecklist(): bool
    {
        return $this->allowDecklist;
    }

    public function setAllowDecklist(bool $allowDecklist): static
    {
        $this->allowDecklist = $allowDecklist;
        return $this;
    }

    public function isRequireDecklist(): bool
    {
        return $this->requireDecklist;
    }

    public function setRequireDecklist(bool $requireDecklist): static
    {
        $this->requireDecklist = $requireDecklist;
        // Si on require, on doit aussi allow
        if ($requireDecklist) {
            $this->allowDecklist = true;
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

    public function getTournamentConfig(): ?array
    {
        return $this->tournamentConfig;
    }

    public function setTournamentConfig(?array $tournamentConfig): static
    {
        $this->tournamentConfig = $tournamentConfig ?? [];
        return $this;
    }

    /**
     * @return Collection<int, TournamentRound>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(TournamentRound $round): static
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds->add($round);
            $round->setTournament($this);
        }
        return $this;
    }

    public function removeRound(TournamentRound $round): static
    {
        if ($this->rounds->removeElement($round)) {
            if ($round->getTournament() === $this) {
                $round->setTournament(null);
            }
        }
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
            $match->setTournament($this);
        }
        return $this;
    }

    public function removeMatch(TournamentMatch $match): static
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getTournament() === $this) {
                $match->setTournament(null);
            }
        }
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES SPÉCIFIQUES =============

    public function canStart(): bool
    {
        return $this->currentPhase === self::PHASE_REGISTRATION 
            && $this->isApproved() 
            && $this->getCurrentParticipants() >= 2;
    }

    public function isSwissPhase(): bool
    {
        return $this->currentPhase === self::PHASE_SWISS;
    }

    public function isTopCutPhase(): bool
    {
        return $this->currentPhase === self::PHASE_TOP_CUT;
    }

    public function isTournamentFinished(): bool
    {
        return $this->currentPhase === self::PHASE_FINISHED;
    }

    public function startTournament(): static
    {
        if (!$this->canStart()) {
            throw new \LogicException('Le tournoi ne peut pas être démarré dans son état actuel');
        }

        $this->currentPhase = self::PHASE_SWISS;
        $this->startedAt = new \DateTimeImmutable();
        $this->setStatus(self::STATUS_IN_PROGRESS);
        
        // Calculer le nombre de rounds Swiss si pas défini
        if (!$this->swissRounds) {
            $this->calculateSwissRounds();
        }

        return $this;
    }

    public function startTopCut(): static
    {
        if ($this->currentPhase !== self::PHASE_SWISS) {
            throw new \LogicException('Le top cut ne peut être démarré que depuis la phase Swiss');
        }

        $this->currentPhase = self::PHASE_TOP_CUT;
        $this->currentRound = 1;
        return $this;
    }

    public function finishTournament(): static
    {
        $this->currentPhase = self::PHASE_FINISHED;
        $this->finishedAt = new \DateTimeImmutable();
        $this->setStatus(self::STATUS_FINISHED);
        return $this;
    }

    public function nextRound(): static
    {
        $this->currentRound++;
        return $this;
    }

    public function calculateSwissRounds(): void
    {
        $playerCount = $this->getCurrentParticipants();
        
        if ($playerCount <= 8) {
            $this->swissRounds = 3;
        } elseif ($playerCount <= 16) {
            $this->swissRounds = 4;
        } elseif ($playerCount <= 32) {
            $this->swissRounds = 5;
        } elseif ($playerCount <= 64) {
            $this->swissRounds = 6;
        } elseif ($playerCount <= 128) {
            $this->swissRounds = 7;
        } else {
            $this->swissRounds = 8;
        }
    }

    public function calculateTopCutSize(): void
    {
        $playerCount = $this->getCurrentParticipants();
        
        if ($playerCount >= 32) {
            $this->topCutSize = 8;
        } elseif ($playerCount >= 16) {
            $this->topCutSize = 4;
        } else {
            $this->topCutSize = null; // Pas de top cut
        }
    }

    public function needsTopCut(): bool
    {
        return $this->topCutSize !== null && $this->topCutSize > 0;
    }

    public function getProgress(): array
    {
        $totalPhases = $this->needsTopCut() ? 2 : 1;
        $currentPhaseProgress = 0;
        
        if ($this->isSwissPhase() && $this->swissRounds > 0) {
            $currentPhaseProgress = ($this->currentRound / $this->swissRounds) * 100;
        } elseif ($this->isTopCutPhase() && $this->topCutSize > 0) {
            // Pour l'élimination, calculer basé sur les matchs restants
            $totalTopCutRounds = log($this->topCutSize, 2);
            $currentPhaseProgress = ($this->currentRound / $totalTopCutRounds) * 100;
        } elseif ($this->isTournamentFinished()) {
            $currentPhaseProgress = 100;
        }

        return [
            'phase' => $this->currentPhase,
            'round' => $this->currentRound,
            'total_rounds_swiss' => $this->swissRounds,
            'top_cut_size' => $this->topCutSize,
            'phase_progress' => min(100, $currentPhaseProgress),
            'overall_progress' => $this->calculateOverallProgress()
        ];
    }

    private function calculateOverallProgress(): float
    {
        if ($this->isTournamentFinished()) {
            return 100.0;
        }

        $swissWeight = 0.7; // 70% du tournoi = Swiss
        $topCutWeight = 0.3; // 30% du tournoi = Top Cut

        $swissProgress = 0;
        if ($this->swissRounds > 0) {
            if ($this->isSwissPhase()) {
                $swissProgress = ($this->currentRound / $this->swissRounds) * $swissWeight * 100;
            } elseif ($this->isTopCutPhase() || $this->isTournamentFinished()) {
                $swissProgress = $swissWeight * 100; // Swiss terminé
            }
        }

        $topCutProgress = 0;
        if ($this->needsTopCut()) {
            if ($this->isTopCutPhase()) {
                $totalTopCutRounds = log($this->topCutSize, 2);
                $topCutProgress = ($this->currentRound / $totalTopCutRounds) * $topCutWeight * 100;
            } elseif ($this->isTournamentFinished()) {
                $topCutProgress = $topCutWeight * 100; // Top Cut terminé
            }
        } else {
            // Pas de top cut, Swiss = 100%
            return min(100, ($this->currentRound / $this->swissRounds) * 100);
        }

        return min(100, $swissProgress + $topCutProgress);
    }

    public function getTournamentFormatDisplayName(): string
    {
        return match($this->tournamentFormat) {
            self::FORMAT_SWISS => 'Swiss',
            self::FORMAT_ELIMINATION => 'Élimination directe',
            self::FORMAT_ROUND_ROBIN => 'Round Robin',
            default => 'Inconnu'
        };
    }

    public function getCurrentPhaseDisplayName(): string
    {
        return match($this->currentPhase) {
            self::PHASE_REGISTRATION => 'Inscriptions',
            self::PHASE_SWISS => 'Phase Swiss',
            self::PHASE_TOP_CUT => 'Top Cut',
            self::PHASE_FINISHED => 'Terminé',
            default => 'Inconnu'
        };
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->startedAt) {
            return null;
        }

        $end = $this->finishedAt ?? new \DateTimeImmutable();
        return $this->startedAt->diff($end);
    }

    public function getEstimatedDuration(): int
    {
        $baseTime = 0;
        
        // Temps estimé pour la phase Swiss
        if ($this->swissRounds) {
            $baseTime += $this->swissRounds * ($this->matchTimer + ($this->breakTimer ?? 10));
        }
        
        // Temps estimé pour le Top Cut
        if ($this->needsTopCut()) {
            $topCutRounds = log($this->topCutSize, 2);
            $baseTime += $topCutRounds * ($this->matchTimer + ($this->breakTimer ?? 10));
        }
        
        return $baseTime;
    }

    public function updateStandings(array $newStandings): static
    {
        $this->standings = $newStandings;
        $this->updateTimestamp();
        return $this;
    }

    public function updatePairings(array $newPairings): static
    {
        $this->pairings = $newPairings;
        $this->updateTimestamp();
        return $this;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->tournamentConfig[$key] ?? $default;
    }

    public function setConfigValue(string $key, mixed $value): static
    {
        $config = $this->tournamentConfig ?? [];
        $config[$key] = $value;
        $this->tournamentConfig = $config;
        return $this;
    }
}