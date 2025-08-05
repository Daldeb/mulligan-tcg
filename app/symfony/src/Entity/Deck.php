<?php

namespace App\Entity;

use App\Repository\DeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeckRepository::class)]
#[ORM\Table(name: 'deck')]
class Deck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le titre du deck est requis')]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'Le titre doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $archetype = null; // "Aggro", "Control", "Combo", etc.

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = false; // Visible sur le site

    #[ORM\Column(type: 'boolean')]
    private bool $validDeck = false; // Deck complet et jouable

    #[ORM\Column(type: 'integer')]
    private int $totalCards = 0; // Nombre total de cartes dans le deck

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $hearthstoneClass = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $averageCost = null; // Coût moyen du deck

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $deckcode = null; // Deckcode pour import/export

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $externalSource = null; // "hearthpwn", "edhrec", "tcgplayer", etc.

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $externalUrl = null; // URL d'origine si importé

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null; // Date de publication publique

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLikedAt = null;
    /**
     * Créateur du deck (peut être null pour decks importés)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * Jeu auquel appartient le deck
     */
    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le jeu est requis')]
    private ?Game $game = null;

    /**
     * Format du deck (Standard, Wild, etc.)
     */
    #[ORM\ManyToOne(targetEntity: GameFormat::class)]
    #[ORM\JoinColumn(name: 'game_format_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le format est requis')]
    private ?GameFormat $gameFormat = null;

    /**
     * @var Collection<int, DeckCard>
     */
    #[ORM\OneToMany(mappedBy: 'deck', targetEntity: DeckCard::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $deckCards;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->deckCards = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
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

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getArchetype(): ?string
    {
        return $this->archetype;
    }

    public function setArchetype(?string $archetype): static
    {
        $this->archetype = $archetype;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        
        // Si on publie le deck, marquer la date de publication
        if ($isPublic && !$this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function isValidDeck(): bool
    {
        return $this->validDeck;
    }

    public function setValidDeck(bool $validDeck): static
    {
        $this->validDeck = $validDeck;
        return $this;
    }

    public function getTotalCards(): int
    {
        return $this->totalCards;
    }

    public function setTotalCards(int $totalCards): static
    {
        $this->totalCards = $totalCards;
        return $this;
    }

    public function getAverageCost(): ?float
    {
        return $this->averageCost;
    }

    public function setAverageCost(?float $averageCost): static
    {
        $this->averageCost = $averageCost;
        return $this;
    }

    public function getDeckcode(): ?string
    {
        return $this->deckcode;
    }

    public function setDeckcode(?string $deckcode): static
    {
        $this->deckcode = $deckcode;
        return $this;
    }

    public function getExternalSource(): ?string
    {
        return $this->externalSource;
    }

    public function setExternalSource(?string $externalSource): static
    {
        $this->externalSource = $externalSource;
        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): static
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function incrementLikes(): static
    {
        $this->likesCount++;
        $this->lastLikedAt = new \DateTimeImmutable();
        return $this;
    }

    public function decrementLikes(): static
    {
        $this->likesCount = max(0, $this->likesCount - 1);
        return $this;
    }

    public function getLastLikedAt(): ?\DateTimeImmutable
    {
        return $this->lastLikedAt;
    }

    public function setLastLikedAt(?\DateTimeImmutable $lastLikedAt): static
    {
        $this->lastLikedAt = $lastLikedAt;
        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): static
    {
        $this->externalUrl = $externalUrl;
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

    public function getHearthstoneClass(): ?string
    {
        return $this->hearthstoneClass;
    }

    public function setHearthstoneClass(?string $hearthstoneClass): static
    {
        $this->hearthstoneClass = $hearthstoneClass;
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

    public function getGameFormat(): ?GameFormat
    {
        return $this->gameFormat;
    }

    public function setGameFormat(?GameFormat $gameFormat): static
    {
        $this->gameFormat = $gameFormat;
        return $this;
    }

    /**
     * @return Collection<int, DeckCard>
     */
    public function getDeckCards(): Collection
    {
        return $this->deckCards;
    }

    public function addDeckCard(DeckCard $deckCard): static
    {
        if (!$this->deckCards->contains($deckCard)) {
            $this->deckCards->add($deckCard);
            $deckCard->setDeck($this);
        }

        return $this;
    }

    public function removeDeckCard(DeckCard $deckCard): static
    {
        if ($this->deckCards->removeElement($deckCard)) {
            if ($deckCard->getDeck() === $this) {
                $deckCard->setDeck(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Méthodes utilitaires

    /**
     * Vérifie si le deck appartient à un utilisateur
     */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    /**
     * Vérifie si c'est un deck importé d'une source externe
     */
    public function isImported(): bool
    {
        return $this->externalSource !== null;
    }

    /**
     * Vérifie si le deck peut être publié (doit être valide)
     */
    public function canBePublished(): bool
    {
        return $this->validDeck;
    }

    /**
     * Recalcule les statistiques du deck (cartes, coût moyen, validité)
     */
    public function recalculateStats(): void
    {
        $totalCards = 0;
        $totalCost = 0;
        $cardsWithCost = 0;

        foreach ($this->deckCards as $deckCard) {
            $quantity = $deckCard->getQuantity();
            $totalCards += $quantity;

            // Calculer le coût moyen (selon le jeu)
            $cost = null;
            if ($this->game->getSlug() === 'hearthstone' && $deckCard->getHearthstoneCard()) {
                $cost = $deckCard->getHearthstoneCard()->getCost();
            } elseif ($this->game->getSlug() === 'pokemon' && $deckCard->getPokemonCard()) {
                // Pokemon n'a pas de coût de mana, mais on pourrait calculer autre chose
                $cost = null;
            }

            if ($cost !== null) {
                $totalCost += ($cost * $quantity);
                $cardsWithCost += $quantity;
            }
        }

        $this->totalCards = $totalCards;
        $this->averageCost = $cardsWithCost > 0 ? ($totalCost / $cardsWithCost) : null;

        // Déterminer la validité selon le jeu
        $this->validDeck = $this->isValidForGame();
        
        $this->updateTimestamp();
    }

    /**
     * Vérifie si le deck est valide selon les règles du jeu
     */
    private function isValidForGame(): bool
    {
        switch ($this->game->getSlug()) {
            case 'hearthstone':
                return $this->isValidHearthstoneDeck();
            case 'pokemon':
                return $this->isValidPokemonDeck();
            case 'magic':
                return $this->isValidMagicDeck();
            default:
                return false;
        }
    }

    /**
     * Validation Hearthstone : 30 cartes, max 1 légendaire, max 2 autres
     */
    private function isValidHearthstoneDeck(): bool
    {
        if ($this->totalCards !== 30) {
            return false;
        }

        foreach ($this->deckCards as $deckCard) {
            $card = $deckCard->getHearthstoneCard();
            if (!$card) continue;

            $quantity = $deckCard->getQuantity();
            
            // Vérification des limites par rareté
            if ($card->getRarity() === 'LEGENDARY' && $quantity > 1) {
                return false;
            } elseif ($card->getRarity() !== 'LEGENDARY' && $quantity > 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validation Pokemon : 60 cartes, max 4 exemplaires (sauf énergie de base)
     */
    private function isValidPokemonDeck(): bool
    {
        if ($this->totalCards !== 60) {
            return false;
        }

        foreach ($this->deckCards as $deckCard) {
            $quantity = $deckCard->getQuantity();
            
            // Règle générale : max 4 exemplaires
            if ($quantity > 4) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validation Magic : 60+ cartes (Commander = 100), max 4 exemplaires
     */
    private function isValidMagicDeck(): bool
    {
        $format = $this->gameFormat->getSlug();
        
        // Commander = exactement 100 cartes
        if ($format === 'commander' && $this->totalCards !== 100) {
            return false;
        }
        
        // Autres formats = minimum 60 cartes
        if ($format !== 'commander' && $this->totalCards < 60) {
            return false;
        }

        foreach ($this->deckCards as $deckCard) {
            $quantity = $deckCard->getQuantity();
            
            // Commander : max 1 exemplaire (sauf terrains de base)
            if ($format === 'commander' && $quantity > 1) {
                // TODO: Vérifier si c'est un terrain de base
                return false;
            }
            
            // Autres formats : max 4 exemplaires
            if ($format !== 'commander' && $quantity > 4) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtient le nom d'affichage complet du deck
     */
    public function getDisplayName(): string
    {
        $name = $this->title;
        
        if ($this->archetype) {
            $name .= " ({$this->archetype})";
        }
        
        return $name;
    }

    /**
     * Obtient le nom de l'auteur (pseudo ou "Deck importé")
     */
    public function getAuthorName(): string
    {
        return $this->user ? $this->user->getPseudo() : 'Deck importé';
    }
}