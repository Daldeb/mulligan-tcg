<?php

namespace App\Entity;

use App\Repository\CommentVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentVoteRepository::class)]
#[ORM\Table(name: 'comment_vote')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_COMMENT', columns: ['user_id', 'comment_id'])]
class CommentVote
{
    public const TYPE_UP = 'UP';
    public const TYPE_DOWN = 'DOWN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Comment $comment;

    #[ORM\Column(type: 'string', length: 4)]
    private string $type;

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

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function setComment(Comment $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_UP, self::TYPE_DOWN])) {
            throw new \InvalidArgumentException("Invalid vote type");
        }
        $this->type = $type;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
