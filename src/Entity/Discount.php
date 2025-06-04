<?php

namespace App\Entity;

use App\Repository\DiscountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscountRepository::class)]
#[ORM\Table(name: 'discounts')]
class Discount
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'discount_id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'discount_percent', type: Types::SMALLINT)] // short -> smallint
    private int $discountPercent;

    #[ORM\OneToMany(targetEntity: SocialStatus::class, mappedBy: 'discount')]
    private Collection $socialStatuses;

    #[ORM\OneToMany(targetEntity: Visit::class, mappedBy: 'discount')]
    private Collection $visits;

    public function __construct(int $discountPercent)
    {
        $this->discountPercent = $discountPercent;
        $this->socialStatuses = new ArrayCollection();
        $this->visits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscountPercent(): int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(int $discountPercent): static
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    /**
     * @return Collection<int, SocialStatus>
     */
    public function getSocialStatuses(): Collection
    {
        return $this->socialStatuses;
    }

    public function addSocialStatus(SocialStatus $socialStatus): static
    {
        if (!$this->socialStatuses->contains($socialStatus)) {
            $this->socialStatuses->add($socialStatus);
            $socialStatus->setDiscount($this);
        }
        return $this;
    }

    public function removeSocialStatus(SocialStatus $socialStatus): static
    {
        if ($this->socialStatuses->removeElement($socialStatus)) {
            // set the owning side to null (unless already changed)
            if ($socialStatus->getDiscount() === $this) {
                //$socialStatus->setDiscount(null); // Ошибка, non-nullable
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Visit>
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    public function addVisit(Visit $visit): static
    {
        if (!$this->visits->contains($visit)) {
            $this->visits->add($visit);
            $visit->setDiscount($this);
        }
        return $this;
    }

    public function removeVisit(Visit $visit): static
    {
        if ($this->visits->removeElement($visit)) {
            if ($visit->getDiscount() === $this) {
                $visit->setDiscount(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->discountPercent . '%';
    }
}