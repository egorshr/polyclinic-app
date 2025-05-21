<?php

// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $username;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column(length: 50)]
    private string $role;

    #[ORM\Column(length: 19)] // Format YYYY-MM-DD HH:MM:SS
    private string $createdAt;

    public function __construct(string $username, string $passwordHash, string $role = 'user', ?string $createdAt = null)
    {
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $symfonyRoles = ['ROLE_USER'];
        $normalizedCurrentRole = strtoupper($this->role);

        if ($normalizedCurrentRole !== 'USER' && !empty($normalizedCurrentRole)) {
            $roleToAdd = str_starts_with($normalizedCurrentRole, 'ROLE_') ? $normalizedCurrentRole : 'ROLE_' . $normalizedCurrentRole;
            if ($roleToAdd !== 'ROLE_USER') {
                $symfonyRoles[] = $roleToAdd;
            }
        }
        return array_unique($symfonyRoles);
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}