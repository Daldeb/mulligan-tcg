<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\ManyToOne(targetEntity: Forum::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Forum $forum = null;

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isPinned = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isLocked = false;

    // Nouveaux champs pour les fonctionnalités Reddit-like

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null; // Images, fichiers joints

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $linkUrl = null; // URL externe

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $linkPreview = null; // Métadonnées du lien

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = null; // Tags du post

    #[ORM\Column(type: 'string', length: 50)]
    private string $postType = 'text'; // 'text', 'link', 'image'

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et setters existants

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): self
    {
        $this->forum = $forum;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;
        return $this;
    }

    // Nouveaux getters et setters pour les fonctionnalités Reddit-like

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): self
    {
        $this->linkUrl = $linkUrl;
        return $this;
    }

    public function getLinkPreview(): ?array
    {
        return $this->linkPreview;
    }

    public function setLinkPreview(?array $linkPreview): self
    {
        $this->linkPreview = $linkPreview;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getPostType(): string
    {
        return $this->postType;
    }

    public function setPostType(string $postType): self
    {
        $this->postType = $postType;
        return $this;
    }

    // Méthodes utilitaires

    /**
     * Vérifie si le post a des pièces jointes
     */
    public function hasAttachments(): bool
    {
        return $this->attachments !== null && !empty($this->attachments);
    }

    /**
     * Ajoute une pièce jointe
     */
    public function addAttachment(array $attachment): self
    {
        if ($this->attachments === null) {
            $this->attachments = [];
        }
        $this->attachments[] = $attachment;
        return $this;
    }

    /**
     * Vérifie si le post a un lien externe
     */
    public function hasLink(): bool
    {
        return $this->linkUrl !== null && !empty($this->linkUrl);
    }

    /**
     * Vérifie si le post a des tags
     */
    public function hasTags(): bool
    {
        return $this->tags !== null && !empty($this->tags);
    }

    /**
     * Ajoute un tag
     */
    public function addTag(string $tag): self
    {
        if ($this->tags === null) {
            $this->tags = [];
        }
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    /**
     * Retire un tag
     */
    public function removeTag(string $tag): self
    {
        if ($this->tags !== null) {
            $this->tags = array_values(array_filter($this->tags, fn($t) => $t !== $tag));
        }
        return $this;
    }

    /**
     * Vérifie le type de post
     */
    public function isTextPost(): bool
    {
        return $this->postType === 'text';
    }

    public function isLinkPost(): bool
    {
        return $this->postType === 'link';
    }

    public function isImagePost(): bool
    {
        return $this->postType === 'image';
    }

    /**
     * Met à jour le timestamp de modification
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}