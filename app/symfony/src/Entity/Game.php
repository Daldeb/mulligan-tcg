<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du jeu est requis')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le slug est requis')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets'
    )]
    private ?string $slug = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9a-fA-F]{6}$/',
        message: 'La couleur principale doit être au format hexadécimal (#RRGGBB)'
    )]
    private ?string $primaryColor = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(
        min: 0,
        max: 999,
        notInRangeMessage: 'L\'ordre doit être entre {{ min }} et {{ max }}'
    )]
    private int $displayOrder = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiConfig = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, GameFormat>
     */
    #[ORM\OneToMany(targetEntity: GameFormat::class, mappedBy: 'game', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['displayOrder' => 'ASC', 'name' => 'ASC'])]
    private Collection $formats;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->formats = new ArrayCollection();
    }

    // Getters and Setters

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getApiConfig(): ?array
    {
        return $this->apiConfig;
    }

    public function setApiConfig(?array $apiConfig): static
    {
        $this->apiConfig = $apiConfig;
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

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, GameFormat>
     */
    public function getFormats(): Collection
    {
        return $this->formats;
    }

    public function addFormat(GameFormat $format): static
    {
        if (!$this->formats->contains($format)) {
            $this->formats->add($format);
            $format->setGame($this);
        }

        return $this;
    }

    public function removeFormat(GameFormat $format): static
    {
        if ($this->formats->removeElement($format)) {
            if ($format->getGame() === $this) {
                $format->setGame(null);
            }
        }

        return $this;
    }

    /**
     * Récupère uniquement les formats actifs
     * 
     * @return Collection<int, GameFormat>
     */
    public function getActiveFormats(): Collection
    {
        return $this->formats->filter(fn(GameFormat $format) => $format->isActive());
    }
}