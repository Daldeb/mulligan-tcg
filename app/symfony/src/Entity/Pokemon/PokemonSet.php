<?php

namespace App\Entity\Pokemon;

use App\Entity\Game;
use App\Repository\Pokemon\PokemonSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokemonSetRepository::class)]
#[ORM\Table(name: 'pokemon_set')]
class PokemonSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $externalId = null; // sm9, sv01, etc.

    #[ORM\Column(length: 200)]
    private ?string $name = null; // Team Up, Darkness Ablaze

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $symbolUrl = null;

    #[ORM\Column]
    private ?int $totalCards = null;

    #[ORM\Column]
    private ?int $officialCards = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'pokemonSet', targetEntity: PokemonCard::class)]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    
    public function getExternalId(): ?string { return $this->externalId; }
    public function setExternalId(string $externalId): static { $this->externalId = $externalId; return $this; }
    
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    
    public function getLogoUrl(): ?string { return $this->logoUrl; }
    public function setLogoUrl(?string $logoUrl): static { $this->logoUrl = $logoUrl; return $this; }
    
    public function getSymbolUrl(): ?string { return $this->symbolUrl; }
    public function setSymbolUrl(?string $symbolUrl): static { $this->symbolUrl = $symbolUrl; return $this; }
    
    public function getTotalCards(): ?int { return $this->totalCards; }
    public function setTotalCards(int $totalCards): static { $this->totalCards = $totalCards; return $this; }
    
    public function getOfficialCards(): ?int { return $this->officialCards; }
    public function setOfficialCards(int $officialCards): static { $this->officialCards = $officialCards; return $this; }
    
    public function getGame(): ?Game { return $this->game; }
    public function setGame(?Game $game): static { $this->game = $game; return $this; }
    
    public function getCards(): Collection { return $this->cards; }
    public function addCard(PokemonCard $card): static { $this->cards->add($card); $card->setPokemonSet($this); return $this; }
    public function removeCard(PokemonCard $card): static { $this->cards->removeElement($card); return $this; }
    
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}