<?php

namespace App\Entity\Hearthstone;

use App\Repository\Hearthstone\HearthstoneCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HearthstoneCardRepository::class)]
#[ORM\Table(name: 'hearthstone_card')]
class HearthstoneCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $externalId = null; // EX1_116, CS2_235, etc.

    #[ORM\Column]
    private ?int $dbfId = null; // CRUCIAL: 559, 1234, etc. pour deckcode mapping

    #[ORM\Column(length: 200)]
    private ?string $name = null; // Leeroy Jenkins, Boule de feu

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null; // MODIFIÉ: nullable pour images manquantes

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $artist = null; // Gabe from Penny Arcade

    #[ORM\Column(nullable: true)]
    private ?int $cost = null; // Coût en mana (0-10+)

    #[ORM\Column(nullable: true)]
    private ?int $attack = null; // Attaque (pour créatures/armes)

    #[ORM\Column(nullable: true)]
    private ?int $health = null; // Vie (pour créatures) / Durabilité (armes)

    #[ORM\Column(length: 50)]
    private ?string $cardClass = null; // MAGE, WARRIOR, NEUTRAL, etc.

    #[ORM\Column(length: 50)]
    private ?string $cardType = null; // MINION, SPELL, WEAPON, HERO, etc.

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rarity = null; // COMMON, RARE, EPIC, LEGENDARY

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null; // Description de la carte

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $flavor = null; // Texte d'ambiance

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $mechanics = null; // ["BATTLECRY", "CHARGE"], etc.

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $faction = null; // ALLIANCE, HORDE, NEUTRAL

    #[ORM\Column(type: 'boolean')]
    private bool $isStandardLegal = false; // Légal en Standard

    #[ORM\Column(type: 'boolean')]
    private bool $isWildLegal = true; // Légal en Wild (par défaut true)

    #[ORM\Column(type: 'boolean')]
    private bool $isCollectible = true; // Carte collectionnable

    #[ORM\ManyToOne(targetEntity: HearthstoneSet::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HearthstoneSet $hearthstoneSet = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }

    public function getExternalId(): ?string { return $this->externalId; }
    public function setExternalId(string $externalId): static { $this->externalId = $externalId; return $this; }

    public function getDbfId(): ?int { return $this->dbfId; }
    public function setDbfId(int $dbfId): static { $this->dbfId = $dbfId; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getArtist(): ?string { return $this->artist; }
    public function setArtist(?string $artist): static { $this->artist = $artist; return $this; }

    public function getCost(): ?int { return $this->cost; }
    public function setCost(?int $cost): static { $this->cost = $cost; return $this; }

    public function getAttack(): ?int { return $this->attack; }
    public function setAttack(?int $attack): static { $this->attack = $attack; return $this; }

    public function getHealth(): ?int { return $this->health; }
    public function setHealth(?int $health): static { $this->health = $health; return $this; }

    public function getCardClass(): ?string { return $this->cardClass; }
    public function setCardClass(string $cardClass): static { $this->cardClass = $cardClass; return $this; }

    public function getCardType(): ?string { return $this->cardType; }
    public function setCardType(string $cardType): static { $this->cardType = $cardType; return $this; }

    public function getRarity(): ?string { return $this->rarity; }
    public function setRarity(?string $rarity): static { $this->rarity = $rarity; return $this; }

    public function getText(): ?string { return $this->text; }
    public function setText(?string $text): static { $this->text = $text; return $this; }

    public function getFlavor(): ?string { return $this->flavor; }
    public function setFlavor(?string $flavor): static { $this->flavor = $flavor; return $this; }

    public function getMechanics(): ?array { return $this->mechanics; }
    public function setMechanics(?array $mechanics): static { $this->mechanics = $mechanics; return $this; }

    public function getFaction(): ?string { return $this->faction; }
    public function setFaction(?string $faction): static { $this->faction = $faction; return $this; }

    public function isStandardLegal(): bool { return $this->isStandardLegal; }
    public function setIsStandardLegal(bool $isStandardLegal): static { $this->isStandardLegal = $isStandardLegal; return $this; }

    public function isWildLegal(): bool { return $this->isWildLegal; }
    public function setIsWildLegal(bool $isWildLegal): static { $this->isWildLegal = $isWildLegal; return $this; }

    public function isCollectible(): bool { return $this->isCollectible; }
    public function setIsCollectible(bool $isCollectible): static { $this->isCollectible = $isCollectible; return $this; }

    public function getHearthstoneSet(): ?HearthstoneSet { return $this->hearthstoneSet; }
    public function setHearthstoneSet(?HearthstoneSet $hearthstoneSet): static { $this->hearthstoneSet = $hearthstoneSet; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getLastSyncedAt(): ?\DateTimeImmutable { return $this->lastSyncedAt; }
    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): static { $this->lastSyncedAt = $lastSyncedAt; return $this; }

    /**
     * Helper: Obtenir les mécaniques sous forme de string
     */
    public function getMechanicsAsString(): string
    {
        return $this->mechanics ? implode(', ', $this->mechanics) : '';
    }

    /**
     * Helper: Vérifier si la carte a une mécanique spécifique
     */
    public function hasMechanic(string $mechanic): bool
    {
        return $this->mechanics && in_array($mechanic, $this->mechanics, true);
    }

    /**
     * Helper: Vérifier si c'est une créature
     */
    public function isMinion(): bool
    {
        return $this->cardType === 'MINION';
    }

    /**
     * Helper: Vérifier si c'est un sort
     */
    public function isSpell(): bool
    {
        return $this->cardType === 'SPELL';
    }

    /**
     * Helper: Vérifier si c'est une arme
     */
    public function isWeapon(): bool
    {
        return $this->cardType === 'WEAPON';
    }
}