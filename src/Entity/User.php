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
#[UniqueEntity(fields: ['username'], message: 'Пользователь с таким именем уже существует')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Имя пользователя не может быть пустым')]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Имя пользователя должно содержать минимум {{ limit }} символа',
        maxMessage: 'Имя пользователя не может быть длиннее {{ limit }} символов'
    )]
    private string $username;

    #[ORM\Column(type: 'string')]
    private string $passwordHash;

    #[Assert\NotBlank(message: 'Пароль не может быть пустым', groups: ['registration'])]
    #[Assert\Length(
        min: 6,
        minMessage: 'Пароль должен содержать минимум {{ limit }} символов',
        groups: ['registration']
    )]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct(string $username = '', string $passwordHash = '')
    {
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Возвращает роли пользователя
     *
     * @return string[] The user roles
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Гарантируем, что у каждого пользователя есть роль ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    /**
     * Возвращает хешированный пароль
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Если у вас сохраняется временный простой пароль, сбросьте его здесь
        $this->plainPassword = null;
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

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}