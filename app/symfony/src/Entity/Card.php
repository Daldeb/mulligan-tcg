<?php

namespace App\Entity;

use App\Repository\CardRepository;
use App\Entity\Game;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    private ?Game $game = null;

    #[ORM\Column(length: 50)]
    private string $gameMode = 'constructed';

    #[ORM\Column(nullable: true)]
    private ?bool $collectible = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    // getters/setters...

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getGameMode(): ?string
    {
        return $this->gameMode;
    }

    public function setGameMode(string $gameMode): static
    {
        $this->gameMode = $gameMode;

        return $this;
    }

    public function isCollectible(): ?bool
    {
        return $this->collectible;
    }

    public function setCollectible(?bool $collectible): static
    {
        $this->collectible = $collectible;

        return $this;
    }

    public function getClassSlug(): ?string
    {
        return $this->classSlug;
    }

    public function setClassSlug(?string $classSlug): static
    {
        $this->classSlug = $classSlug;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
}
