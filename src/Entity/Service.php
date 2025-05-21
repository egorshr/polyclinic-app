<?php

// src/Entity/Service.php
namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    private const VALID_SERVICES = [
        'Портретная съёмка',
        'Семейная фотосессия',
        'Съёмка на документы',
        'Творческая съёмка',
    ];

    public function __construct(string $name)
    {
        if (!in_array($name, self::VALID_SERVICES, true)) {
            throw new InvalidArgumentException('Невалидная услуга');
        }
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public static function getAvailableServices(): array
    {
        return self::VALID_SERVICES;
    }
}