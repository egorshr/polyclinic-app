<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bookings')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $service;

    #[ORM\Column(type: 'string', length: 255)]
    private string $photographer;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    public function __construct(
        string $name,
        string $service,
        string $photographer,
        string $date, // строка формата 'YYYY-MM-DD'
        int $userId
    ) {
        $this->name = $name;
        $this->service = $service;
        $this->photographer = $photographer;
        $this->date = new \DateTime($date);
        $this->userId = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function getPhotographer(): string
    {
        return $this->photographer;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setName(string $name): void { $this->name = $name; }
    public function setService(string $service): void { $this->service = $service; }
    public function setPhotographer(string $photographer): void { $this->photographer = $photographer; }
    public function setDate(\DateTimeInterface $date): void { $this->date = $date; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
}
