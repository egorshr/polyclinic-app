<?php

namespace App\Entity;

use App\Repository\PhotographerRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: PhotographerRepository::class)]
class Photographer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    public function __construct(string $name)
    {
        if (!in_array($name, self::getAvailablePhotographers(), true)) {
            throw new InvalidArgumentException('Невалидный фотограф');
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

    public static function getAvailablePhotographers(): array
    {
        return [
            'Анна Иванова',
            'Игорь Петров',
            'Екатерина Смирнова',
        ];
    }
}
