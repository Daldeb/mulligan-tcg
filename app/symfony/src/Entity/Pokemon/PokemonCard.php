<?php

namespace App\Entity\Pokemon;

use App\Repository\Pokemon\PokemonCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokemonCardRepository::class)]
#[ORM\Table(name: 'pokemon_card')]
class PokemonCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $externalId = null; // sm9-1, sv01-25, etc.

    #[ORM\Column(length: 10)]
    private ?string $localId = null; // 1, 25, 156, etc.

    #[ORM\Column(length: 200)]
    private ?string $name = null; // Celebi et Florizarre GX

    #[ORM\Column(length: 500)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $illustrator = null; // Mitsuhiro Arita

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $rarity = null; // Ultra Rare, Common, etc.

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $types = null; // ["Plante"], ["Feu", "Vol"]

    #[ORM\Column(nullable: true)]
    private ?int $hp = null; // 270, null pour les Dresseurs

    #[ORM\Column(type: 'boolean')]
    private bool $isStandardLegal = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isExpandedLegal = true;

    #[ORM\ManyToOne(targetEntity: PokemonSet::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PokemonSet $pokemonSet = null;

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

    public function getLocalId(): ?string { return $this->localId; }
    public function setLocalId(string $localId): static { $this->localId = $localId; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getIllustrator(): ?string { return $this->illustrator; }
    public function setIllustrator(?string $illustrator): static { $this->illustrator = $illustrator; return $this; }

    public function getRarity(): ?string { return $this->rarity; }
    public function setRarity(?string $rarity): static { $this->rarity = $rarity; return $this; }

    public function getTypes(): ?array { return $this->types; }
    public function setTypes(?array $types): static { $this->types = $types; return $this; }

    public function getHp(): ?int { return $this->hp; }
    public function setHp(?int $hp): static { $this->hp = $hp; return $this; }

    public function isStandardLegal(): bool { return $this->isStandardLegal; }
    public function setIsStandardLegal(bool $isStandardLegal): static { $this->isStandardLegal = $isStandardLegal; return $this; }

    public function isExpandedLegal(): bool { return $this->isExpandedLegal; }
    public function setIsExpandedLegal(bool $isExpandedLegal): static { $this->isExpandedLegal = $isExpandedLegal; return $this; }

    public function getPokemonSet(): ?PokemonSet { return $this->pokemonSet; }
    public function setPokemonSet(?PokemonSet $pokemonSet): static { $this->pokemonSet = $pokemonSet; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getLastSyncedAt(): ?\DateTimeImmutable { return $this->lastSyncedAt; }
    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): static { $this->lastSyncedAt = $lastSyncedAt; return $this; }

    /**
     * Helper: Obtenir les types sous forme de string
     */
    public function getTypesAsString(): string
    {
        return $this->types ? implode(', ', $this->types) : '';
    }

    /**
     * Helper: Vérifier si la carte a un type spécifique
     */
    public function hasType(string $type): bool
    {
        return $this->types && in_array($type, $this->types, true);
    }
}