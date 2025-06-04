<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')] // Имя таблицы как в Kotlin
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")] // autoIncrement
    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $username;

    #[ORM\Column(length: 100, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'first_name', length: 50)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', length: 50)]
    private string $lastName;

    #[ORM\Column(length: 20)]
    private string $role; // Например: 'ROLE_PATIENT', 'ROLE_DOCTOR', 'ROLE_ADMIN'

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    // Связь с Пациентом (если пользователь - пациент)
    #[ORM\OneToOne(targetEntity: Patient::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Patient $patientProfile = null;

    // Связь с Сотрудником (если пользователь - сотрудник)
    #[ORM\OneToOne(targetEntity: Employee::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Employee $employeeProfile = null;

    public function __construct(string $username, string $email, string $firstName, string $lastName, string $role)
    {
        $this->username = $username;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role; // Устанавливаем роль явно
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string // Для PasswordAuthenticatedUserInterface
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getRoles(): array // Для UserInterface
    {
        $roles = [$this->role];
        // Гарантируем наличие ROLE_USER, если это стандартная роль Symfony
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function eraseCredentials(): void
    {
        // Если вы храните временные, чувствительные данные о пользователе, очистите их здесь.
        // $this->plainPassword = null;
    }

    public function getUserIdentifier(): string // Для UserInterface
    {
        return $this->username;
    }

    public function getPatientProfile(): ?Patient
    {
        return $this->patientProfile;
    }

    public function setPatientProfile(?Patient $patientProfile): static
    {
        // set the owning side of the relation if necessary
        if ($patientProfile !== null && $patientProfile->getUser() !== $this) {
            $patientProfile->setUser($this);
        }
        $this->patientProfile = $patientProfile;
        return $this;
    }

    public function getEmployeeProfile(): ?Employee
    {
        return $this->employeeProfile;
    }

    public function setEmployeeProfile(?Employee $employeeProfile): static
    {
        // set the owning side of the relation if necessary
        if ($employeeProfile !== null && $employeeProfile->getUser() !== $this) {
            $employeeProfile->setUser($this);
        }
        $this->employeeProfile = $employeeProfile;
        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}