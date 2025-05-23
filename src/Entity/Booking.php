<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $service;

    #[ORM\Column(length: 255)]
    private string $photographer;

    #[ORM\Column(length: 255)]
    private string $date;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])] // Новое поле
    private DateTimeImmutable $createdAt;

    public function __construct(string $name, string $service, string $photographer, string $date, int $userId)
    {
        $this->name = $name;
        $this->service = $service;
        $this->photographer = $photographer;
        $this->date = $date;
        $this->userId = $userId;
        $this->createdAt = new DateTimeImmutable();
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

    public function getDate(): string
    {
        return $this->date;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }


}