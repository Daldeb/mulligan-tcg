<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà pris')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le pseudo est requis')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le pseudo doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_-]+$/',
        message: 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores'
    )]
    private ?string $pseudo = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $favoriteClass = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * Adresse de l'utilisateur (optionnelle)
     */
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Address $address = null;

    /**
     * Jeux sélectionnés par l'utilisateur pour le super filtre
     * Format: [1, 2, 3] (IDs des jeux)
     * null = aucun filtre appliqué (voir tous les jeux)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $selectedGames = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    // Getters and Setters existants

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTimeImmutable $verificationTokenExpiresAt): static
    {
        $this->verificationTokenExpiresAt = $verificationTokenExpiresAt;
        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): static
    {
        $this->resetPasswordToken = $resetPasswordToken;
        return $this;
    }

    public function getResetPasswordTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetPasswordTokenExpiresAt;
    }

    public function setResetPasswordTokenExpiresAt(?\DateTimeImmutable $resetPasswordTokenExpiresAt): static
    {
        $this->resetPasswordTokenExpiresAt = $resetPasswordTokenExpiresAt;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): ?string
    {
        if ($this->firstName && $this->lastName) {
            return $this->firstName . ' ' . $this->lastName;
        }
        return $this->firstName ?: $this->lastName;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getFavoriteClass(): ?string
    {
        return $this->favoriteClass;
    }

    public function setFavoriteClass(?string $favoriteClass): static
    {
        $this->favoriteClass = $favoriteClass;
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

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * Gestion de l'adresse utilisateur
     */
    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function hasAddress(): bool
    {
        return $this->address !== null;
    }

    // Nouvelles méthodes pour la gestion des jeux sélectionnés

    /**
     * @return int[]|null
     */
    public function getSelectedGames(): ?array
    {
        return $this->selectedGames;
    }

    /**
     * @param int[]|null $selectedGames
     */
    public function setSelectedGames(?array $selectedGames): static
    {
        $this->selectedGames = $selectedGames;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur a sélectionné des jeux
     */
    public function hasSelectedGames(): bool
    {
        return $this->selectedGames !== null && !empty($this->selectedGames);
    }

    /**
     * Vérifie si un jeu spécifique est sélectionné
     */
    public function hasSelectedGame(int $gameId): bool
    {
        return $this->selectedGames !== null && in_array($gameId, $this->selectedGames, true);
    }

    /**
     * Ajoute un jeu à la sélection
     */
    public function addSelectedGame(int $gameId): static
    {
        if ($this->selectedGames === null) {
            $this->selectedGames = [];
        }

        if (!in_array($gameId, $this->selectedGames, true)) {
            $this->selectedGames[] = $gameId;
        }

        return $this;
    }

    /**
     * Supprime un jeu de la sélection
     */
    public function removeSelectedGame(int $gameId): static
    {
        if ($this->selectedGames === null) {
            return $this;
        }

        $this->selectedGames = array_values(array_filter(
            $this->selectedGames,
            fn(int $id) => $id !== $gameId
        ));

        // Si array devient vide, le laisser vide plutôt que null
        return $this;
    }

    /**
     * Vide la sélection de jeux (retour à "tout voir")
     */
    public function clearSelectedGames(): static
    {
        $this->selectedGames = null;
        return $this;
    }

    /**
     * Remplace complètement la sélection de jeux
     * 
     * @param int[] $gameIds
     */
    public function replaceSelectedGames(array $gameIds): static
    {
        // Filtrer pour ne garder que les entiers valides
        $validIds = array_filter($gameIds, fn($id) => is_int($id) && $id > 0);
        $this->selectedGames = empty($validIds) ? null : array_values(array_unique($validIds));
        return $this;
    }

    public function generateVerificationToken(): void
    {
        $this->verificationToken = bin2hex(random_bytes(32));
        $this->verificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
    }

    public function generateResetPasswordToken(): void
    {
        $this->resetPasswordToken = bin2hex(random_bytes(32));
        $this->resetPasswordTokenExpiresAt = new \DateTimeImmutable('+1 hour');
    }

    public function isVerificationTokenValid(): bool
    {
        return $this->verificationToken !== null 
            && $this->verificationTokenExpiresAt !== null 
            && $this->verificationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function isResetPasswordTokenValid(): bool
    {
        return $this->resetPasswordToken !== null 
            && $this->resetPasswordTokenExpiresAt !== null 
            && $this->resetPasswordTokenExpiresAt > new \DateTimeImmutable();
    }

    public function clearVerificationToken(): void
    {
        $this->verificationToken = null;
        $this->verificationTokenExpiresAt = null;
    }

    public function clearResetPasswordToken(): void
    {
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;
    }
}