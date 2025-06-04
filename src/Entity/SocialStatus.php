<?php

namespace App\Entity;

use App\Repository\SocialStatusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialStatusRepository::class)]
#[ORM\Table(name: 'social_statuses')]
class SocialStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Discount::class, inversedBy: 'socialStatuses')]
    #[ORM\JoinColumn(name: 'discount_id', referencedColumnName: 'discount_id', nullable: false)]
    private Discount $discount;

    #[ORM\Column(length: 100)]
    private string $description;

    public function __construct(Discount $discount, string $description)
    {
        $this->discount = $discount;
        $this->description = $description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    public function setDiscount(Discount $discount): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function __toString(): string
    {
        return $this->description;
    }
}