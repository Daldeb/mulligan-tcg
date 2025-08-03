<?php

namespace App\Entity;

use App\Repository\PostSaveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostSaveRepository::class)]
#[ORM\Table(name: 'post_save')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_POST_SAVE', columns: ['user_id', 'post_id'])]
class PostSave
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;
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

    /**
     * Vérifie si cette sauvegarde appartient à un utilisateur spécifique
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user->getId() === $user->getId();
    }

    /**
     * Vérifie si cette sauvegarde concerne un post spécifique
     */
    public function isForPost(Post $post): bool
    {
        return $this->post->getId() === $post->getId();
    }

    /**
     * Récupère l'ID du post sauvegardé (helper)
     */
    public function getPostId(): int
    {
        return $this->post->getId();
    }

    /**
     * Récupère l'ID de l'utilisateur (helper)
     */
    public function getUserId(): int
    {
        return $this->user->getId();
    }

    /**
     * Récupère le titre du post sauvegardé (helper)
     */
    public function getPostTitle(): string
    {
        return $this->post->getTitle();
    }

    /**
     * Récupère le pseudo de l'utilisateur (helper)
     */
    public function getUserPseudo(): string
    {
        return $this->user->getPseudo();
    }
}