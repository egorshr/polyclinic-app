<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    public function __construct(string $name)
    {
        if (!in_array($name, self::getAvailableServices(), true)) {
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
        return [
            'Портретная съёмка',
            'Семейная фотосессия',
            'Съёмка на документы',
            'Творческая съёмка',
        ];
    }
}
