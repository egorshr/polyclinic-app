<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection; // Добавлено
use Doctrine\Common\Collections\Collection;      // Добавлено
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\ManyToMany(targetEntity: Visit::class, mappedBy: "renderedServices")] // mappedBy указывает на свойство в Visit
    private Collection $visits; // Визиты, в которых была оказана эта услуга

    public function __construct(string $name, string $price)
    {
        $this->name = $name;
        $this->price = $price;
        $this->visits = new ArrayCollection(); // Инициализация коллекции
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return Collection<int, Visit>
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    // Методы addVisit/removeVisit не обязательны здесь, если связь управляется из Visit,
    // но могут быть полезны для двунаправленного управления.
    public function addVisit(Visit $visit): static
    {
        if (!$this->visits->contains($visit)) {
            $this->visits->add($visit);
            $visit->addRenderedService($this); // Поддерживаем двунаправленность
        }
        return $this;
    }

    public function removeVisit(Visit $visit): static
    {
        if ($this->visits->removeElement($visit)) {
            $visit->removeRenderedService($this); // Поддерживаем двунаправленность
        }
        return $this;
    }


    public function __toString(): string
    {
        return $this->name;
    }
}