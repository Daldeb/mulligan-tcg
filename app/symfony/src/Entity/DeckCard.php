<?php

namespace App\Entity;

use App\Entity\Hearthstone\HearthstoneCard;
use App\Entity\Pokemon\PokemonCard;
use App\Entity\Magic\MagicCard;
use App\Repository\DeckCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeckCardRepository::class)]
#[ORM\Table(name: 'deck_card')]
class DeckCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'La quantité doit être entre {{ min }} et {{ max }}'
    )]
    private ?int $quantity = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Deck auquel appartient cette carte
     */
    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'deckCards')]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Deck $deck = null;

    /**
     * Carte Hearthstone (si le deck est Hearthstone)
     */
    #[ORM\ManyToOne(targetEntity: HearthstoneCard::class)]
    #[ORM\JoinColumn(name: 'hearthstone_card_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?HearthstoneCard $hearthstoneCard = null;

    /**
     * Carte Pokemon (si le deck est Pokemon)
     */
    #[ORM\ManyToOne(targetEntity: PokemonCard::class)]
    #[ORM\JoinColumn(name: 'pokemon_card_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?PokemonCard $pokemonCard = null;

        /**
     * Carte Magic (si le deck est Pokemon)
     */
    #[ORM\ManyToOne(targetEntity: MagicCard::class)]
    #[ORM\JoinColumn(name: 'magic_card_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?MagicCard $magicCard = null;

    /**
     * Carte Magic (quand l'entité sera créée)
     * TODO: Ajouter quand Magic sera implémenté
     */
    // #[ORM\ManyToOne(targetEntity: MagicCard::class)]
    // #[ORM\JoinColumn(name: 'magic_card_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    // private ?MagicCard $magicCard = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
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

    public function getHearthstoneCard(): ?HearthstoneCard
    {
        return $this->hearthstoneCard;
    }

    public function setHearthstoneCard(?HearthstoneCard $hearthstoneCard): static
    {
        $this->hearthstoneCard = $hearthstoneCard;
        return $this;
    }

    public function getPokemonCard(): ?PokemonCard
    {
        return $this->pokemonCard;
    }

    public function setPokemonCard(?PokemonCard $pokemonCard): static
    {
        $this->pokemonCard = $pokemonCard;
        return $this;
    }

        public function getMagicCard(): ?MagicCard
    {
        return $this->magicCard;
    }

    public function setMagicCard(?MagicCard $magicCard): static
    {
        $this->magicCard = $magicCard;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Méthodes utilitaires

    /**
     * Obtient la carte associée quel que soit le jeu
     */
    public function getCard(): HearthstoneCard|PokemonCard|MagicCard|null
    {
        return $this->hearthstoneCard ?? $this->pokemonCard ?? $this->magicCard ?? null;
    }

    /**
     * Obtient le nom de la carte
     */
    public function getCardName(): ?string
    {
        $card = $this->getCard();
        return $card?->getName();
    }

    /**
     * Obtient l'URL de l'image de la carte
     */
    public function getCardImageUrl(): ?string
    {
        $card = $this->getCard();
        return $card?->getImageUrl();
    }

    /**
     * Obtient le coût de la carte (pour Hearthstone)
     */
    public function getCardCost(): ?int
    {
        if ($this->hearthstoneCard) {
            return $this->hearthstoneCard->getCost();
        }
        
        return null;
    }

    /**
     * Obtient la rareté de la carte
     */
    public function getCardRarity(): ?string
    {
        if ($this->hearthstoneCard) {
            return $this->hearthstoneCard->getRarity();
        }
        
        if ($this->pokemonCard) {
            return $this->pokemonCard->getRarity();
        }
        
        return null;
    }

    /**
     * Vérifie la validité de cette carte dans le deck
     */
    public function isValidInDeck(): bool
    {
        if (!$this->deck || !$this->getCard()) {
            return false;
        }

        $gameSlug = $this->deck->getGame()->getSlug();
        
        switch ($gameSlug) {
            case 'hearthstone':
                return $this->isValidHearthstoneCard();
            case 'pokemon':
                return $this->isValidPokemonCard();
            case 'magic':
                return $this->isValidMagicCard();
            default:
                return false;
        }
    }

    private function isValidHearthstoneCard(): bool
    {
        if (!$this->hearthstoneCard) {
            return false;
        }

        // Vérifier les limites de quantité
        if ($this->hearthstoneCard->getRarity() === 'LEGENDARY' && $this->quantity > 1) {
            return false;
        } elseif ($this->hearthstoneCard->getRarity() !== 'LEGENDARY' && $this->quantity > 2) {
            return false;
        }

        // Vérifier la légalité dans le format
        $format = $this->deck->getGameFormat()->getSlug();
        if ($format === 'standard' && !$this->hearthstoneCard->isStandardLegal()) {
            return false;
        } elseif ($format === 'wild' && !$this->hearthstoneCard->isWildLegal()) {
            return false;
        }

        return true;
    }

    private function isValidPokemonCard(): bool
    {
        if (!$this->pokemonCard) {
            return false;
        }

        // Règle générale : max 4 exemplaires
        if ($this->quantity > 4) {
            return false;
        }

        // Vérifier la légalité dans le format
        $format = $this->deck->getGameFormat()->getSlug();
        if ($format === 'standard' && !$this->pokemonCard->isStandardLegal()) {
            return false;
        } elseif ($format === 'expanded' && !$this->pokemonCard->isExpandedLegal()) {
            return false;
        }

        return true;
    }

    private function isValidMagicCard(): bool
    {
        // TODO: Implémenter quand Magic sera ajouté
        return true;
    }
}