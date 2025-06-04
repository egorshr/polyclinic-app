<?php

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'patientProfile')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private User $user; // Связь с User обязательна

    #[ORM\Column(name: 'first_name', length: 32, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 64, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'middle_name', length: 36, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(length: 10, nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $birthday = null;

    #[ORM\Column(name: 'phone_number', length: 18, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'passport_series', length: 45, nullable: true)]
    private ?string $passportSeries = null;

    #[ORM\Column(name: 'passport_number', length: 45, nullable: true)]
    private ?string $passportNumber = null;

    #[ORM\Column(name: 'passport_issue_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $passportIssueDate = null;

    #[ORM\Column(name: 'passport_issued_by', length: 150, nullable: true)] // Увеличил длину для поля "Кем выдан"
    private ?string $passportIssuedBy = null;

    #[ORM\Column(name: 'address_country', length: 45, nullable: true)]
    private ?string $addressCountry = null;

    #[ORM\Column(name: 'address_region', length: 100, nullable: true)] // Увеличил длину
    private ?string $addressRegion = null;

    #[ORM\Column(name: 'address_locality', length: 100, nullable: true)] // Увеличил длину
    private ?string $addressLocality = null;

    #[ORM\Column(name: 'address_street', length: 150, nullable: true)] // Увеличил длину
    private ?string $addressStreet = null;

    #[ORM\Column(name: 'address_house', length: 20, nullable: true)] // Для номеров типа "12А/3"
    private ?string $addressHouse = null;

    #[ORM\Column(name: 'address_body', length: 20, nullable: true)] // Для "корпус Б", "строение 2"
    private ?string $addressBody = null;

    #[ORM\Column(name: 'address_apartment', length: 20, nullable: true)] // Для номеров квартир/офисов
    private ?string $addressApartment = null; // Изменил на string для гибкости (например, "10Б")

    #[ORM\OneToMany(targetEntity: Visit::class, mappedBy: 'patient')]
    private Collection $visits;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->visits = new ArrayCollection();

        // Предзаполнение имени и фамилии из User, если они есть у User
        // и еще не установлены в этом объекте Patient (что верно для нового)
        if ($user->getFirstName() && $this->firstName === null) {
            $this->setFirstName($user->getFirstName());
        }
        if ($user->getLastName() && $this->lastName === null) {
            $this->setLastName($user->getLastName());
        }
    }

    // Далее идут все геттеры и сеттеры.
    // Важно, чтобы они были сгенерированы/обновлены для поддержки nullable типов.
    // Например:
    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }
    public function getMiddleName(): ?string { return $this->middleName; }
    public function setMiddleName(?string $middleName): static { $this->middleName = $middleName; return $this; }
    public function getGender(): ?Gender { return $this->gender; }
    public function setGender(?Gender $gender): static { $this->gender = $gender; return $this; }
    public function getBirthday(): ?DateTimeImmutable { return $this->birthday; }
    public function setBirthday(?DateTimeImmutable $birthday): static { $this->birthday = $birthday; return $this; }
    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }
    public function getPassportSeries(): ?string { return $this->passportSeries; }
    public function setPassportSeries(?string $passportSeries): static { $this->passportSeries = $passportSeries; return $this; }
    public function getPassportNumber(): ?string { return $this->passportNumber; }
    public function setPassportNumber(?string $passportNumber): static { $this->passportNumber = $passportNumber; return $this; }
    public function getPassportIssueDate(): ?DateTimeImmutable { return $this->passportIssueDate; }
    public function setPassportIssueDate(?DateTimeImmutable $passportIssueDate): static { $this->passportIssueDate = $passportIssueDate; return $this; }
    public function getPassportIssuedBy(): ?string { return $this->passportIssuedBy; }
    public function setPassportIssuedBy(?string $passportIssuedBy): static { $this->passportIssuedBy = $passportIssuedBy; return $this; }
    public function getAddressCountry(): ?string { return $this->addressCountry; }
    public function setAddressCountry(?string $addressCountry): static { $this->addressCountry = $addressCountry; return $this; }
    public function getAddressRegion(): ?string { return $this->addressRegion; }
    public function setAddressRegion(?string $addressRegion): static { $this->addressRegion = $addressRegion; return $this; }
    public function getAddressLocality(): ?string { return $this->addressLocality; }
    public function setAddressLocality(?string $addressLocality): static { $this->addressLocality = $addressLocality; return $this; }
    public function getAddressStreet(): ?string { return $this->addressStreet; }
    public function setAddressStreet(?string $addressStreet): static { $this->addressStreet = $addressStreet; return $this; }
    public function getAddressHouse(): ?string { return $this->addressHouse; }
    public function setAddressHouse(?string $addressHouse): static { $this->addressHouse = $addressHouse; return $this; }
    public function getAddressBody(): ?string { return $this->addressBody; }
    public function setAddressBody(?string $addressBody): static { $this->addressBody = $addressBody; return $this; }
    public function getAddressApartment(): ?string { return $this->addressApartment; } // Было ?int, изменил на ?string
    public function setAddressApartment(?string $addressApartment): static { $this->addressApartment = $addressApartment; return $this; }

    /** @return Collection<int, Visit> */
    public function getVisits(): Collection { return $this->visits; }
    public function addVisit(Visit $visit): static { if (!$this->visits->contains($visit)) { $this->visits->add($visit); $visit->setPatient($this); } return $this; }
    public function removeVisit(Visit $visit): static { if ($this->visits->removeElement($visit)) { if ($visit->getPatient() === $this) { /* $visit->setPatient(null); Ошибка, non-nullable */ } } return $this; }
    public function getFullName(): string { return trim($this->lastName . ' ' . $this->firstName . ' ' . $this->middleName); }
    public function __toString(): string { return $this->getFullName() ?: 'Новый пациент'; }
}