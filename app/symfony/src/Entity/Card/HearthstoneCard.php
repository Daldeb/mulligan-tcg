<?php

namespace App\Entity\Card;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "hearthstone_card")]
class HearthstoneCard
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "string", length: 50)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(name: "dbf_id", type: "integer")]
    private int $dbfId;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private string $name;

    #[ORM\Column(name: "card_set", type: "string", length: 255, nullable: true)]
    private ?string $cardSet = null;

    #[ORM\Column(name: "rarity", type: "string", length: 255, nullable: true)]
    private ?string $rarity = null;

    #[ORM\Column(name: "type", type: "string", length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: "card_class", type: "string", length: 255, nullable: true)]
    private ?string $cardClass = null;

    #[ORM\Column(name: "image_path", type: "string", length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: "data", type: "json")]
    private array $data = [];

    #[ORM\Column(name: "imported_at", type: "datetime")]
    private \DateTimeInterface $importedAt;

    #[ORM\Column(name: "locale", type: "string", length: 10)]
    private string $locale;

    public function __construct()
    {
        $this->importedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDbfId(): int
    {
        return $this->dbfId;
    }

    public function setDbfId(int $dbfId): self
    {
        $this->dbfId = $dbfId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCardSet(): ?string
    {
        return $this->cardSet;
    }

    public function setCardSet(?string $cardSet): self
    {
        $this->cardSet = $cardSet;
        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): self
    {
        $this->rarity = $rarity;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCardClass(): ?string
    {
        return $this->cardClass;
    }

    public function setCardClass(?string $cardClass): self
    {
        $this->cardClass = $cardClass;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getImportedAt(): \DateTimeInterface
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeInterface $importedAt): self
    {
        $this->importedAt = $importedAt;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }
}
