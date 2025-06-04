<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
#[ORM\Table(name: 'schedules')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private Employee $employee;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $date;

    #[ORM\Column(name: 'time_from', type: Types::TIME_IMMUTABLE)]
    private DateTimeImmutable $timeFrom;

    #[ORM\Column(name: 'time_to', type: Types::TIME_IMMUTABLE)]
    private DateTimeImmutable $timeTo;

    public function __construct(Employee $employee, DateTimeImmutable $date, DateTimeImmutable $timeFrom, DateTimeImmutable $timeTo)
    {
        $this->employee = $employee;
        $this->date = $date;
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getTimeFrom(): DateTimeImmutable
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(DateTimeImmutable $timeFrom): static
    {
        $this->timeFrom = $timeFrom;
        return $this;
    }

    public function getTimeTo(): DateTimeImmutable
    {
        return $this->timeTo;
    }

    public function setTimeTo(DateTimeImmutable $timeTo): static
    {
        $this->timeTo = $timeTo;
        return $this;
    }
}