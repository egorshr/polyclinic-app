<?php

namespace App\Entity;

use App\Enum\VisitStatus;
use App\Repository\VisitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: VisitRepository::class)]
#[ORM\Table(name: 'visits')]
class Visit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Discount::class, inversedBy: 'visits')]
    #[ORM\JoinColumn(name: 'discount_id', referencedColumnName: 'discount_id', nullable: true)]
    private ?Discount $discount;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'visits')]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false)]
    private Patient $patient;

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'visits')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private Employee $employee;

    #[ORM\Column(name: 'visit_date_and_time', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $dateAndTime;

    #[ORM\Column(length: 10, enumType: VisitStatus::class)]
    private VisitStatus $status;

    // Если в рамках одного визита может быть оказано несколько услуг,
    // нужна связь ManyToMany с Service через промежуточную таблицу.
    // Например:
    // #[ORM\ManyToMany(targetEntity: Service::class)]
    // #[ORM\JoinTable(name: 'visit_services')]
    // private Collection $servicesRendered;

    public function __construct(
        Patient $patient,
        Employee $employee,
        DateTimeImmutable $dateAndTime,
        VisitStatus $status,
        ?Discount $discount = null
    ) {
        $this->patient = $patient;
        $this->employee = $employee;
        $this->dateAndTime = $dateAndTime;
        $this->status = $status;
        $this->discount = $discount;
        // $this->servicesRendered = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function setPatient(Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): static
    {
        $this->employee = $employee;
        return $this;
    }

    public function getDateAndTime(): DateTimeImmutable
    {
        return $this->dateAndTime;
    }

    public function setDateAndTime(DateTimeImmutable $dateAndTime): static
    {
        $this->dateAndTime = $dateAndTime;
        return $this;
    }

    public function getStatus(): VisitStatus
    {
        return $this->status;
    }

    public function setStatus(VisitStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    // Геттеры/сеттеры для $servicesRendered, если они добавлены
}