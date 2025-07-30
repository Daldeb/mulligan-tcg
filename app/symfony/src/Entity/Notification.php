<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['user_id', 'is_read'], name: 'idx_user_read')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_user_created')]
class Notification
{
    // Types de notifications
    public const TYPE_ROLE_APPROVED = 'role_approved';
    public const TYPE_ROLE_REJECTED = 'role_rejected';
    public const TYPE_EVENT_CREATED = 'event_created';
    public const TYPE_REPLY_RECEIVED = 'reply_received';
    public const TYPE_MESSAGE_RECEIVED = 'message_received';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [
        self::TYPE_ROLE_APPROVED,
        self::TYPE_ROLE_REJECTED,
        self::TYPE_EVENT_CREATED,
        self::TYPE_REPLY_RECEIVED,
        self::TYPE_MESSAGE_RECEIVED
    ])]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    // Champs optionnels pour actions
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $actionUrl = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $actionLabel = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $icon = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
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

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function setActionUrl(?string $actionUrl): static
    {
        $this->actionUrl = $actionUrl;
        return $this;
    }

    public function getActionLabel(): ?string
    {
        return $this->actionLabel;
    }

    public function setActionLabel(?string $actionLabel): static
    {
        $this->actionLabel = $actionLabel;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    // Méthodes utilitaires

    /**
     * Marque la notification comme lue
     */
    public function markAsRead(): static
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie si la notification est récente (moins de 24h)
     */
    public function isRecent(): bool
    {
        $dayAgo = new \DateTimeImmutable('-24 hours');
        return $this->createdAt > $dayAgo;
    }

    /**
     * Retourne un format d'affichage pour le temps écoulé
     */
    public function getTimeAgo(): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->createdAt);

        if ($diff->days > 0) {
            return $diff->days === 1 ? 'il y a 1 jour' : "il y a {$diff->days} jours";
        } elseif ($diff->h > 0) {
            return $diff->h === 1 ? 'il y a 1 heure' : "il y a {$diff->h} heures";
        } elseif ($diff->i > 0) {
            return $diff->i === 1 ? 'il y a 1 minute' : "il y a {$diff->i} minutes";
        } else {
            return 'à l\'instant';
        }
    }

    /**
     * Retourne les données contextuelles d'une clé spécifique
     */
    public function getDataValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Ajoute ou met à jour une donnée contextuelle
     */
    public function setDataValue(string $key, mixed $value): static
    {
        if ($this->data === null) {
            $this->data = [];
        }
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Retourne tous les types de notifications disponibles
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_ROLE_APPROVED,
            self::TYPE_ROLE_REJECTED,
            self::TYPE_EVENT_CREATED,
            self::TYPE_REPLY_RECEIVED,
            self::TYPE_MESSAGE_RECEIVED,
        ];
    }

    /**
     * Retourne le libellé d'affichage du type
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_ROLE_APPROVED => 'Demande approuvée',
            self::TYPE_ROLE_REJECTED => 'Demande refusée',
            self::TYPE_EVENT_CREATED => 'Nouvel événement',
            self::TYPE_REPLY_RECEIVED => 'Nouvelle réponse',
            self::TYPE_MESSAGE_RECEIVED => 'Nouveau message',
            default => 'Notification'
        };
    }
}