<?php

namespace App\Entity\Magic;

use App\Entity\Game;
use App\Repository\Magic\MagicSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MagicSetRepository::class)]
#[ORM\Table(name: 'magic_set')]
#[UniqueEntity(fields: ['scryfallId'], message: 'This Scryfall set ID already exists')]
#[UniqueEntity(fields: ['setCode'], message: 'This set code already exists')]
#[ORM\Index(columns: ['set_code'], name: 'idx_magic_set_code')]
#[ORM\Index(columns: ['scryfall_id'], name: 'idx_magic_set_scryfall_id')]
#[ORM\Index(columns: ['set_type'], name: 'idx_magic_set_type')]
class MagicSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Scryfall ID is required')]
    private ?string $scryfallId = null;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Set code is required')]
    #[Assert\Length(max: 10)]
    private ?string $setCode = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Set name is required')]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [
        'core', 'expansion', 'masters', 'draft_innovation', 'commander', 
        'planechase', 'archenemy', 'vanguard', 'funny', 'starter', 
        'box', 'promo', 'token', 'memorabilia', 'treasure_chest',
        'spellbook', 'from_the_vault', 'premium_deck', 'duel_deck',
        'arsenal', 'minigame'
    ])]
    private ?string $setType = 'expansion';

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $setUri = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $scryfallUri = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $uri = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $searchUri = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    /**
     * Jeu parent (Magic)
     */
    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Game is required')]
    private ?Game $game = null;

    /**
     * @var Collection<int, MagicCard>
     */
    #[ORM\OneToMany(mappedBy: 'magicSet', targetEntity: MagicCard::class, cascade: ['persist', 'remove'])]
    private Collection $magicCards;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->magicCards = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScryfallId(): ?string
    {
        return $this->scryfallId;
    }

    public function setScryfallId(string $scryfallId): static
    {
        $this->scryfallId = $scryfallId;
        return $this;
    }

    public function getSetCode(): ?string
    {
        return $this->setCode;
    }

    public function setSetCode(string $setCode): static
    {
        $this->setCode = strtolower($setCode);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSetType(): ?string
    {
        return $this->setType;
    }

    public function setSetType(string $setType): static
    {
        $this->setType = $setType;
        return $this;
    }

    public function getSetUri(): ?string
    {
        return $this->setUri;
    }

    public function setSetUri(?string $setUri): static
    {
        $this->setUri = $setUri;
        return $this;
    }

    public function getScryfallUri(): ?string
    {
        return $this->scryfallUri;
    }

    public function setScryfallUri(?string $scryfallUri): static
    {
        $this->scryfallUri = $scryfallUri;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(?string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    public function getSearchUri(): ?string
    {
        return $this->searchUri;
    }

    public function setSearchUri(?string $searchUri): static
    {
        $this->searchUri = $searchUri;
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

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): static
    {
        $this->lastSyncedAt = $lastSyncedAt;
        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    /**
     * @return Collection<int, MagicCard>
     */
    public function getMagicCards(): Collection
    {
        return $this->magicCards;
    }

    public function addMagicCard(MagicCard $magicCard): static
    {
        if (!$this->magicCards->contains($magicCard)) {
            $this->magicCards->add($magicCard);
            $magicCard->setMagicSet($this);
        }

        return $this;
    }

    public function removeMagicCard(MagicCard $magicCard): static
    {
        if ($this->magicCards->removeElement($magicCard)) {
            if ($magicCard->getMagicSet() === $this) {
                $magicCard->setMagicSet(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires

    /**
     * Met à jour le timestamp de dernière modification
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Marque le set comme synchronisé
     */
    public function markAsSynced(): void
    {
        $this->lastSyncedAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }

    /**
     * Vérifie si le set est un set principal (expansion/core)
     */
    public function isMainSet(): bool
    {
        return in_array($this->setType, ['core', 'expansion', 'draft_innovation']);
    }

    /**
     * Vérifie si le set est un produit Commander
     */
    public function isCommanderProduct(): bool
    {
        return $this->setType === 'commander';
    }

    /**
     * Vérifie si le set est un produit promotionnel
     */
    public function isPromoSet(): bool
    {
        return in_array($this->setType, ['promo', 'token', 'memorabilia']);
    }

    /**
     * Retourne le nom d'affichage du set avec code
     */
    public function getDisplayName(): string
    {
        return $this->name . ' (' . strtoupper($this->setCode) . ')';
    }

    /**
     * Compte le nombre de cartes importées dans ce set
     */
    public function getCardCount(): int
    {
        return $this->magicCards->count();
    }

    /**
     * Vérifie si le set a des cartes importées
     */
    public function hasCards(): bool
    {
        return $this->magicCards->count() > 0;
    }

    /**
     * Recalcule les statistiques du set
     */
    public function recalculateStats(): void
    {
        $this->updateTimestamp();
    }
}