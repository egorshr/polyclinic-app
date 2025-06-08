<?php

namespace App\Entity;

use App\Enum\VisitStatus;
use App\Repository\VisitRepository;
use Doctrine\Common\Collections\ArrayCollection; // Добавлено
use Doctrine\Common\Collections\Collection;      // Добавлено
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
    private ?Discount $discount = null; // Изменено на nullable, если скидка не всегда есть

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

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: "visits")] // Связь ManyToMany
    #[ORM\JoinTable(name: 'visit_services')]                               // Имя промежуточной таблицы
    #[ORM\JoinColumn(name: 'visit_id', referencedColumnName: 'id', onDelete: 'CASCADE')] // onDelete для каскадного удаления связей
    #[ORM\InverseJoinColumn(name: 'service_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $renderedServices; // Коллекция оказанных услуг

    public function __construct(
        Patient $patient,
        Employee $employee,
        DateTimeImmutable $dateAndTime,
        VisitStatus $status,
        ?Discount $discount = null
        // Услуги теперь добавляются через addRenderedService, а не через конструктор напрямую
    ) {
        $this->patient = $patient;
        $this->employee = $employee;
        $this->dateAndTime = $dateAndTime;
        $this->status = $status;
        $this->discount = $discount;
        $this->renderedServices = new ArrayCollection(); // Инициализация коллекции
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

    /**
     * @return Collection<int, Service>
     */
    public function getRenderedServices(): Collection
    {
        return $this->renderedServices;
    }

    public function addRenderedService(Service $service): static
    {
        if (!$this->renderedServices->contains($service)) {
            $this->renderedServices->add($service);
            // Если в Service есть $visits, то $service->addVisit($this); но это не обязательно здесь
        }
        return $this;
    }

    public function removeRenderedService(Service $service): static
    {
        $this->renderedServices->removeElement($service);
        // Если в Service есть $visits, то $service->removeVisit($this);
        return $this;
    }
}