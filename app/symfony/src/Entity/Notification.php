<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['user_id', 'is_read'], name: 'idx_user_read')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_user_created')]
#[ORM\Index(columns: ['type', 'created_at'], name: 'idx_type_created')]
class Notification
{
    // Types de notifications - Rôles et système
    public const TYPE_ROLE_APPROVED = 'role_approved';
    public const TYPE_ROLE_REJECTED = 'role_rejected';
    public const TYPE_REPLY_RECEIVED = 'reply_received';
    public const TYPE_MESSAGE_RECEIVED = 'message_received';

    // Types de notifications - Événements admin
    public const TYPE_EVENT_APPROVED = 'event_approved';
    public const TYPE_EVENT_REJECTED = 'event_rejected';
    public const TYPE_EVENT_DELETED = 'event_deleted';
    public const TYPE_EVENT_CREATED = 'event_created';


    // Types de notifications - Événements temporels
    public const TYPE_EVENT_APPROACHING = 'event_approaching';        // 7 jours avant
    public const TYPE_EVENT_SOON = 'event_soon';                      // 2 jours avant
    public const TYPE_EVENT_STARTING = 'event_starting';              // 1h avant début
    public const TYPE_EVENT_ENDING_SOON = 'event_ending_soon';        // 1h avant fin
    public const TYPE_EVENT_FINISHED = 'event_finished';              // Après la fin

    // Types de notifications - Inscriptions et participations
    public const TYPE_NEW_REGISTRATION = 'new_registration';          // Nouvelle inscription (→ organisateur)
    public const TYPE_REGISTRATION_CONFIRMED = 'registration_confirmed'; // Inscription confirmée (→ participant)
    public const TYPE_CHECKIN_REMINDER = 'checkin_reminder';          // Rappel check-in (→ participant)
    public const TYPE_TOURNAMENT_STARTED = 'tournament_started';       // Tournoi commencé (→ participants)
    public const TYPE_MATCH_ASSIGNED = 'match_assigned';              // Match assigné (→ participants)
    public const TYPE_ROUND_STARTED = 'round_started';                // Nouveau round (→ participants)

    // Types de notifications - Événements suivis
    public const TYPE_FOLLOWED_EVENT_UPDATE = 'followed_event_update'; // Événement suivi mis à jour

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: [
        self::TYPE_ROLE_APPROVED,
        self::TYPE_ROLE_REJECTED,
        self::TYPE_REPLY_RECEIVED,
        self::TYPE_MESSAGE_RECEIVED,
        self::TYPE_EVENT_APPROVED,
        self::TYPE_EVENT_REJECTED,
        self::TYPE_EVENT_DELETED,
        self::TYPE_EVENT_APPROACHING,
        self::TYPE_EVENT_SOON,
        self::TYPE_EVENT_STARTING,
        self::TYPE_EVENT_ENDING_SOON,
        self::TYPE_EVENT_FINISHED,
        self::TYPE_NEW_REGISTRATION,
        self::TYPE_REGISTRATION_CONFIRMED,
        self::TYPE_CHECKIN_REMINDER,
        self::TYPE_TOURNAMENT_STARTED,
        self::TYPE_MATCH_ASSIGNED,
        self::TYPE_ROUND_STARTED,
        self::TYPE_FOLLOWED_EVENT_UPDATE
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

    // Champs pour actions et apparence
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $actionUrl = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $actionLabel = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $icon = null;

    // Relations optionnelles pour référencer des entités
    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Event $relatedEvent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $relatedUser = null;

    // Priorité de la notification (pour tri et affichage)
    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\Choice(choices: ['low', 'normal', 'high', 'urgent'])]
    private string $priority = 'normal';

    // Catégorie pour regroupement (optionnel)
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ============= GETTERS & SETTERS =============

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

    public function getRelatedEvent(): ?Event
    {
        return $this->relatedEvent;
    }

    public function setRelatedEvent(?Event $relatedEvent): static
    {
        $this->relatedEvent = $relatedEvent;
        return $this;
    }

    public function getRelatedUser(): ?User
    {
        return $this->relatedUser;
    }

    public function setRelatedUser(?User $relatedUser): static
    {
        $this->relatedUser = $relatedUser;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    // ============= MÉTHODES UTILITAIRES =============

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
     * Vérifie si la notification est urgente
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    /**
     * Vérifie si la notification est liée aux événements
     */
    public function isEventRelated(): bool
    {
        return str_starts_with($this->type, 'event_') || 
               str_starts_with($this->type, 'tournament_') ||
               str_starts_with($this->type, 'registration_') ||
               in_array($this->type, [
                   self::TYPE_NEW_REGISTRATION,
                   self::TYPE_CHECKIN_REMINDER,
                   self::TYPE_MATCH_ASSIGNED,
                   self::TYPE_ROUND_STARTED,
                   self::TYPE_FOLLOWED_EVENT_UPDATE
               ]);
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
            // Système et rôles
            self::TYPE_ROLE_APPROVED,
            self::TYPE_ROLE_REJECTED,
            self::TYPE_REPLY_RECEIVED,
            self::TYPE_MESSAGE_RECEIVED,
            
            // Événements admin
            self::TYPE_EVENT_APPROVED,
            self::TYPE_EVENT_REJECTED,
            self::TYPE_EVENT_DELETED,
            
            // Événements temporels
            self::TYPE_EVENT_APPROACHING,
            self::TYPE_EVENT_SOON,
            self::TYPE_EVENT_STARTING,
            self::TYPE_EVENT_ENDING_SOON,
            self::TYPE_EVENT_FINISHED,
            
            // Inscriptions et participations
            self::TYPE_NEW_REGISTRATION,
            self::TYPE_REGISTRATION_CONFIRMED,
            self::TYPE_CHECKIN_REMINDER,
            self::TYPE_TOURNAMENT_STARTED,
            self::TYPE_MATCH_ASSIGNED,
            self::TYPE_ROUND_STARTED,
            
            // Événements suivis
            self::TYPE_FOLLOWED_EVENT_UPDATE,
        ];
    }

    /**
     * Retourne les types par catégorie
     */
    public static function getTypesByCategory(): array
    {
        return [
            'admin' => [
                self::TYPE_ROLE_APPROVED,
                self::TYPE_ROLE_REJECTED,
                self::TYPE_EVENT_APPROVED,
                self::TYPE_EVENT_REJECTED,
                self::TYPE_EVENT_DELETED,
            ],
            'events' => [
                self::TYPE_EVENT_APPROACHING,
                self::TYPE_EVENT_SOON,
                self::TYPE_EVENT_STARTING,
                self::TYPE_EVENT_ENDING_SOON,
                self::TYPE_EVENT_FINISHED,
                self::TYPE_FOLLOWED_EVENT_UPDATE,
            ],
            'participation' => [
                self::TYPE_NEW_REGISTRATION,
                self::TYPE_REGISTRATION_CONFIRMED,
                self::TYPE_CHECKIN_REMINDER,
            ],
            'tournament' => [
                self::TYPE_TOURNAMENT_STARTED,
                self::TYPE_MATCH_ASSIGNED,
                self::TYPE_ROUND_STARTED,
            ],
            'social' => [
                self::TYPE_REPLY_RECEIVED,
                self::TYPE_MESSAGE_RECEIVED,
            ]
        ];
    }

    /**
     * Retourne le libellé d'affichage du type
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            // Admin et rôles
            self::TYPE_ROLE_APPROVED => 'Demande approuvée',
            self::TYPE_ROLE_REJECTED => 'Demande refusée',
            self::TYPE_EVENT_APPROVED => 'Événement approuvé',
            self::TYPE_EVENT_REJECTED => 'Événement refusé',
            self::TYPE_EVENT_DELETED => 'Événement supprimé',
            
            // Événements temporels
            self::TYPE_EVENT_APPROACHING => 'Événement approche',
            self::TYPE_EVENT_SOON => 'Événement bientôt',
            self::TYPE_EVENT_STARTING => 'Événement commence',
            self::TYPE_EVENT_ENDING_SOON => 'Événement se termine',
            self::TYPE_EVENT_FINISHED => 'Événement terminé',
            
            // Participations
            self::TYPE_NEW_REGISTRATION => 'Nouvelle inscription',
            self::TYPE_REGISTRATION_CONFIRMED => 'Inscription confirmée',
            self::TYPE_CHECKIN_REMINDER => 'Rappel check-in',
            
            // Tournois
            self::TYPE_TOURNAMENT_STARTED => 'Tournoi commencé',
            self::TYPE_MATCH_ASSIGNED => 'Match assigné',
            self::TYPE_ROUND_STARTED => 'Nouveau round',
            
            // Suivi
            self::TYPE_FOLLOWED_EVENT_UPDATE => 'Événement suivi mis à jour',
            
            // Social
            self::TYPE_REPLY_RECEIVED => 'Nouvelle réponse',
            self::TYPE_MESSAGE_RECEIVED => 'Nouveau message',
            
            default => 'Notification'
        };
    }

    /**
     * Retourne l'icône par défaut pour le type
     */
    public function getDefaultIcon(): string
    {
        return match($this->type) {
            // Admin et rôles
            self::TYPE_ROLE_APPROVED, self::TYPE_EVENT_APPROVED => '✅',
            self::TYPE_ROLE_REJECTED, self::TYPE_EVENT_REJECTED => 'pi-times-circle',
            self::TYPE_EVENT_DELETED => 'pi-trash',
            
            // Événements temporels
            self::TYPE_EVENT_APPROACHING, self::TYPE_EVENT_SOON => 'pi-clock',
            self::TYPE_EVENT_STARTING => 'pi-play-circle',
            self::TYPE_EVENT_ENDING_SOON => 'pi-exclamation-triangle',
            self::TYPE_EVENT_FINISHED => 'pi-check',
            
            // Participations
            self::TYPE_NEW_REGISTRATION, self::TYPE_REGISTRATION_CONFIRMED => 'pi-user-plus',
            self::TYPE_CHECKIN_REMINDER => 'pi-sign-in',
            
            // Tournois
            self::TYPE_TOURNAMENT_STARTED => 'pi-trophy',
            self::TYPE_MATCH_ASSIGNED, self::TYPE_ROUND_STARTED => 'pi-users',
            
            // Suivi et social
            self::TYPE_FOLLOWED_EVENT_UPDATE => 'pi-heart',
            self::TYPE_REPLY_RECEIVED, self::TYPE_MESSAGE_RECEIVED => 'pi-comment',
            
            default => 'pi-bell'
        };
    }

    /**
     * Retourne la couleur/sévérité par défaut pour le type
     */
    public function getDefaultSeverity(): string
    {
        return match($this->type) {
            // Succès
            self::TYPE_ROLE_APPROVED, self::TYPE_EVENT_APPROVED, 
            self::TYPE_REGISTRATION_CONFIRMED, self::TYPE_TOURNAMENT_STARTED => 'success',
            
            // Erreurs
            self::TYPE_ROLE_REJECTED, self::TYPE_EVENT_REJECTED, 
            self::TYPE_EVENT_DELETED => 'error',
            
            // Avertissements
            self::TYPE_EVENT_ENDING_SOON, self::TYPE_CHECKIN_REMINDER => 'warn',
            
            // Informations importantes
            self::TYPE_EVENT_STARTING, self::TYPE_MATCH_ASSIGNED, 
            self::TYPE_ROUND_STARTED => 'info',
            
            // Normal
            default => 'info'
        };
    }

    /**
     * Configure automatiquement l'icône et l'URL d'action selon le type
     */
    public function autoConfigureAppearance(): static
    {
        if (!$this->icon) {
            $this->icon = $this->getDefaultIcon();
        }
        
        // Configuration automatique des URLs d'action
        if (!$this->actionUrl && $this->relatedEvent) {
            $eventId = $this->relatedEvent->getId();
            
            $this->actionUrl = match($this->type) {
                self::TYPE_EVENT_REJECTED => "/mes-evenements?edit={$eventId}",
                self::TYPE_EVENT_STARTING, self::TYPE_EVENT_ENDING_SOON, 
                self::TYPE_TOURNAMENT_STARTED => "/evenements/{$eventId}",
                self::TYPE_CHECKIN_REMINDER => "/evenements/{$eventId}?checkin=1",
                self::TYPE_MATCH_ASSIGNED => "/tournois/{$eventId}/matches",
                default => "/evenements/{$eventId}"
            };
        }
        
        // Configuration automatique des labels d'action
        if (!$this->actionLabel && $this->actionUrl) {
            $this->actionLabel = match($this->type) {
                self::TYPE_EVENT_REJECTED => 'Modifier',
                self::TYPE_CHECKIN_REMINDER => 'Faire le check-in',
                self::TYPE_MATCH_ASSIGNED => 'Voir les matchs',
                self::TYPE_NEW_REGISTRATION => 'Voir les participants',
                default => 'Voir'
            };
        }
        
        return $this;
    }
}