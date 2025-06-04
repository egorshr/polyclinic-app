<?php

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employees')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'speciality_id', referencedColumnName: 'id', nullable: false)]
    private Specialty $specialty;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'employeeProfile')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private User $user;

    #[ORM\Column(name: 'first_name', length: 32)]
    private string $firstName;

    #[ORM\Column(name: 'middle_name', length: 36, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(name: 'last_name', length: 64)]
    private string $lastName;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $birthday;

    #[ORM\Column(length: 10, enumType: Gender::class)]
    private Gender $gender;

    #[ORM\Column(name: 'phone_number', length: 18)]
    private string $phoneNumber;

    #[ORM\Column(name: 'duration_of_visit', type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $durationOfVisit = null; // Хранит только время

    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'employee', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $schedules;

    #[ORM\OneToMany(targetEntity: Visit::class, mappedBy: 'employee')]
    private Collection $visits;

    public function __construct(
        User $user,
        Specialty $specialty,
        string $firstName,
        string $lastName,
        DateTimeImmutable $birthday,
        Gender $gender,
        string $phoneNumber
    ) {
        $this->user = $user;
        $this->specialty = $specialty;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthday = $birthday;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->schedules = new ArrayCollection();
        $this->visits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpecialty(): Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): static
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
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

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): static
    {
        $this->middleName = $middleName;
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

    public function getBirthday(): DateTimeImmutable
    {
        return $this->birthday;
    }

    public function setBirthday(DateTimeImmutable $birthday): static
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function setGender(Gender $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getDurationOfVisit(): ?DateTimeImmutable
    {
        return $this->durationOfVisit;
    }

    public function setDurationOfVisit(?DateTimeImmutable $durationOfVisit): static
    {
        $this->durationOfVisit = $durationOfVisit;
        return $this;
    }

    /**
     * @return Collection<int, Schedule>
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): static
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setEmployee($this);
        }
        return $this;
    }

    public function removeSchedule(Schedule $schedule): static
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getEmployee() === $this) {
                // $schedule->setEmployee(null); // Это вызовет ошибку, т.к. employeeId non-nullable
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Visit>
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    public function addVisit(Visit $visit): static
    {
        if (!$this->visits->contains($visit)) {
            $this->visits->add($visit);
            $visit->setEmployee($this);
        }
        return $this;
    }

    public function removeVisit(Visit $visit): static
    {
        if ($this->visits->removeElement($visit)) {
            // set the owning side to null (unless already changed)
            if ($visit->getEmployee() === $this) {
                // $visit->setEmployee(null); // Ошибка, non-nullable
            }
        }
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->lastName . ' ' . $this->firstName . ' ' . $this->middleName);
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}