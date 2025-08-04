<?php

namespace App\Entity\Magic;

use App\Repository\Magic\MagicCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MagicCardRepository::class)]
#[ORM\Table(name: 'magic_card')]
#[ORM\Index(columns: ['oracle_id'], name: 'idx_magic_card_oracle_id')]
#[ORM\Index(columns: ['scryfall_id'], name: 'idx_magic_card_scryfall_id')]
#[ORM\Index(columns: ['is_standard_legal'], name: 'idx_magic_card_standard_legal')]
#[ORM\Index(columns: ['is_commander_legal'], name: 'idx_magic_card_commander_legal')]
#[ORM\Index(columns: ['rarity'], name: 'idx_magic_card_rarity')]
#[ORM\Index(columns: ['cmc'], name: 'idx_magic_card_cmc')]
class MagicCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Oracle ID is required')]
    private ?string $oracleId = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Scryfall ID is required')]
    private ?string $scryfallId = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Card name is required')]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $printedName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lang = 'en';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $manaCost = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $cmc = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $typeLine = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $printedTypeLine = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $oracleText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $printedText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $flavorText = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $power = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $toughness = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $colors = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $colorIdentity = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $producedMana = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: ['common', 'uncommon', 'rare', 'mythic', 'special', 'bonus'])]
    private ?string $rarity = 'common';

    #[ORM\Column(type: 'boolean')]
    private bool $isStandardLegal = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isCommanderLegal = false;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $imageUrlLarge = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $artist = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $artistIds = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $illustrationId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $layout = 'normal';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $frame = '2015';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $frameEffects = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $borderColor = 'black';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $securityStamp = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $watermark = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPromo = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isReprint = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isReserved = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isFullArt = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isTextless = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isBooster = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isDigital = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $games = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $finishes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $multiverseIds = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mtgoId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $arenaId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tcgplayerId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cardmarketId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $edhrecRank = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pennyRank = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    /**
     * Set Magic auquel appartient cette carte
     */
    #[ORM\ManyToOne(targetEntity: MagicSet::class)]
    #[ORM\JoinColumn(name: 'magic_set_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Magic set is required')]
    private ?MagicSet $magicSet = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOracleId(): ?string
    {
        return $this->oracleId;
    }

    public function setOracleId(string $oracleId): static
    {
        $this->oracleId = $oracleId;
        return $this;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrintedName(): ?string
    {
        return $this->printedName;
    }

    public function setPrintedName(?string $printedName): static
    {
        $this->printedName = $printedName;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    public function getManaCost(): ?string
    {
        return $this->manaCost;
    }

    public function setManaCost(?string $manaCost): static
    {
        $this->manaCost = $manaCost;
        return $this;
    }

    public function getCmc(): ?float
    {
        return $this->cmc;
    }

    public function setCmc(?float $cmc): static
    {
        $this->cmc = $cmc;
        return $this;
    }

    public function getTypeLine(): ?string
    {
        return $this->typeLine;
    }

    public function setTypeLine(?string $typeLine): static
    {
        $this->typeLine = $typeLine;
        return $this;
    }

    public function getPrintedTypeLine(): ?string
    {
        return $this->printedTypeLine;
    }

    public function setPrintedTypeLine(?string $printedTypeLine): static
    {
        $this->printedTypeLine = $printedTypeLine;
        return $this;
    }

    public function getOracleText(): ?string
    {
        return $this->oracleText;
    }

    public function setOracleText(?string $oracleText): static
    {
        $this->oracleText = $oracleText;
        return $this;
    }

    public function getPrintedText(): ?string
    {
        return $this->printedText;
    }

    public function setPrintedText(?string $printedText): static
    {
        $this->printedText = $printedText;
        return $this;
    }

    public function getFlavorText(): ?string
    {
        return $this->flavorText;
    }

    public function setFlavorText(?string $flavorText): static
    {
        $this->flavorText = $flavorText;
        return $this;
    }

    public function getPower(): ?string
    {
        return $this->power;
    }

    public function setPower(?string $power): static
    {
        $this->power = $power;
        return $this;
    }

    public function getToughness(): ?string
    {
        return $this->toughness;
    }

    public function setToughness(?string $toughness): static
    {
        $this->toughness = $toughness;
        return $this;
    }

    public function getColors(): ?array
    {
        return $this->colors;
    }

    public function setColors(?array $colors): static
    {
        $this->colors = $colors;
        return $this;
    }

    public function getColorIdentity(): ?array
    {
        return $this->colorIdentity;
    }

    public function setColorIdentity(?array $colorIdentity): static
    {
        $this->colorIdentity = $colorIdentity;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getProducedMana(): ?array
    {
        return $this->producedMana;
    }

    public function setProducedMana(?array $producedMana): static
    {
        $this->producedMana = $producedMana;
        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): static
    {
        $this->rarity = $rarity;
        return $this;
    }

    public function isStandardLegal(): bool
    {
        return $this->isStandardLegal;
    }

    public function setIsStandardLegal(bool $isStandardLegal): static
    {
        $this->isStandardLegal = $isStandardLegal;
        return $this;
    }

    public function isCommanderLegal(): bool
    {
        return $this->isCommanderLegal;
    }

    public function setIsCommanderLegal(bool $isCommanderLegal): static
    {
        $this->isCommanderLegal = $isCommanderLegal;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getImageUrlLarge(): ?string
    {
        return $this->imageUrlLarge;
    }

    public function setImageUrlLarge(?string $imageUrlLarge): static
    {
        $this->imageUrlLarge = $imageUrlLarge;
        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): static
    {
        $this->artist = $artist;
        return $this;
    }

    public function getArtistIds(): ?array
    {
        return $this->artistIds;
    }

    public function setArtistIds(?array $artistIds): static
    {
        $this->artistIds = $artistIds;
        return $this;
    }

    public function getIllustrationId(): ?string
    {
        return $this->illustrationId;
    }

    public function setIllustrationId(?string $illustrationId): static
    {
        $this->illustrationId = $illustrationId;
        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function setLayout(?string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function getFrame(): ?string
    {
        return $this->frame;
    }

    public function setFrame(?string $frame): static
    {
        $this->frame = $frame;
        return $this;
    }

    public function getFrameEffects(): ?array
    {
        return $this->frameEffects;
    }

    public function setFrameEffects(?array $frameEffects): static
    {
        $this->frameEffects = $frameEffects;
        return $this;
    }

    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    public function setBorderColor(?string $borderColor): static
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    public function getSecurityStamp(): ?string
    {
        return $this->securityStamp;
    }

    public function setSecurityStamp(?string $securityStamp): static
    {
        $this->securityStamp = $securityStamp;
        return $this;
    }

    public function getWatermark(): ?string
    {
        return $this->watermark;
    }

    public function setWatermark(?string $watermark): static
    {
        $this->watermark = $watermark;
        return $this;
    }

    public function isPromo(): bool
    {
        return $this->isPromo;
    }

    public function setIsPromo(bool $isPromo): static
    {
        $this->isPromo = $isPromo;
        return $this;
    }

    public function isReprint(): bool
    {
        return $this->isReprint;
    }

    public function setIsReprint(bool $isReprint): static
    {
        $this->isReprint = $isReprint;
        return $this;
    }

    public function isReserved(): bool
    {
        return $this->isReserved;
    }

    public function setIsReserved(bool $isReserved): static
    {
        $this->isReserved = $isReserved;
        return $this;
    }

    public function isFullArt(): bool
    {
        return $this->isFullArt;
    }

    public function setIsFullArt(bool $isFullArt): static
    {
        $this->isFullArt = $isFullArt;
        return $this;
    }

    public function isTextless(): bool
    {
        return $this->isTextless;
    }

    public function setIsTextless(bool $isTextless): static
    {
        $this->isTextless = $isTextless;
        return $this;
    }

    public function isBooster(): bool
    {
        return $this->isBooster;
    }

    public function setIsBooster(bool $isBooster): static
    {
        $this->isBooster = $isBooster;
        return $this;
    }

    public function isDigital(): bool
    {
        return $this->isDigital;
    }

    public function setIsDigital(bool $isDigital): static
    {
        $this->isDigital = $isDigital;
        return $this;
    }

    public function getGames(): ?array
    {
        return $this->games;
    }

    public function setGames(?array $games): static
    {
        $this->games = $games;
        return $this;
    }

    public function getFinishes(): ?array
    {
        return $this->finishes;
    }

    public function setFinishes(?array $finishes): static
    {
        $this->finishes = $finishes;
        return $this;
    }

    public function getMultiverseIds(): ?array
    {
        return $this->multiverseIds;
    }

    public function setMultiverseIds(?array $multiverseIds): static
    {
        $this->multiverseIds = $multiverseIds;
        return $this;
    }

    public function getMtgoId(): ?int
    {
        return $this->mtgoId;
    }

    public function setMtgoId(?int $mtgoId): static
    {
        $this->mtgoId = $mtgoId;
        return $this;
    }

    public function getArenaId(): ?int
    {
        return $this->arenaId;
    }

    public function setArenaId(?int $arenaId): static
    {
        $this->arenaId = $arenaId;
        return $this;
    }

    public function getTcgplayerId(): ?int
    {
        return $this->tcgplayerId;
    }

    public function setTcgplayerId(?int $tcgplayerId): static
    {
        $this->tcgplayerId = $tcgplayerId;
        return $this;
    }

    public function getCardmarketId(): ?int
    {
        return $this->cardmarketId;
    }

    public function setCardmarketId(?int $cardmarketId): static
    {
        $this->cardmarketId = $cardmarketId;
        return $this;
    }

    public function getEdhrecRank(): ?int
    {
        return $this->edhrecRank;
    }

    public function setEdhrecRank(?int $edhrecRank): static
    {
        $this->edhrecRank = $edhrecRank;
        return $this;
    }

    public function getPennyRank(): ?int
    {
        return $this->pennyRank;
    }

    public function setPennyRank(?int $pennyRank): static
    {
        $this->pennyRank = $pennyRank;
        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;
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

    public function getMagicSet(): ?MagicSet
    {
        return $this->magicSet;
    }

    public function setMagicSet(?MagicSet $magicSet): static
    {
        $this->magicSet = $magicSet;
        return $this;
    }

    // Méthodes utilitaires

    /**
     * Retourne le nom d'affichage selon la langue disponible
     */
    public function getDisplayName(): string
    {
        return $this->printedName ?? $this->name ?? 'Unknown Card';
    }

    /**
     * Retourne le type d'affichage selon la langue disponible
     */
    public function getDisplayTypeLine(): string
    {
        return $this->printedTypeLine ?? $this->typeLine ?? '';
    }

    /**
     * Retourne le texte d'affichage selon la langue disponible
     */
    public function getDisplayText(): ?string
    {
        return $this->printedText ?? $this->oracleText;
    }

    /**
     * Vérifie si la carte est une créature
     */
    public function isCreature(): bool
    {
        $typeLine = $this->getDisplayTypeLine();
        return stripos($typeLine, 'creature') !== false || stripos($typeLine, 'créature') !== false;
    }

    /**
     * Vérifie si la carte est un terrain
     */
    public function isLand(): bool
    {
        $typeLine = $this->getDisplayTypeLine();
        return stripos($typeLine, 'land') !== false || stripos($typeLine, 'terrain') !== false;
    }

    /**
     * Vérifie si la carte est légendaire
     */
    public function isLegendary(): bool
    {
        $typeLine = $this->getDisplayTypeLine();
        return stripos($typeLine, 'legendary') !== false || stripos($typeLine, 'légendaire') !== false;
    }

    /**
     * Retourne les couleurs sous forme de string pour l'affichage
     */
    public function getColorsString(): string
    {
        if (empty($this->colors)) {
            return 'Colorless';
        }
        
        return implode('', $this->colors);
    }

    /**
     * Retourne l'identité de couleur sous forme de string
     */
    public function getColorIdentityString(): string
    {
        if (empty($this->colorIdentity)) {
            return 'C';
        }
        
        return implode('', $this->colorIdentity);
    }

    /**
     * Vérifie si la carte peut être utilisée comme commandant
     */
    public function canBeCommander(): bool
    {
        return $this->isLegendary() && $this->isCreature() && $this->isCommanderLegal();
    }

    /**
     * Met à jour le timestamp de dernière modification
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Marque la carte comme synchronisée
     */
    public function markAsSynced(): void
    {
        $this->lastSyncedAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }
}