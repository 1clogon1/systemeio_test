<?php

namespace App\Entity;

use App\Repository\CouponsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponsRepository::class)]
class Coupons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $discount_value;

    #[ORM\Column(type: 'string', length: 255)]
    private string $discount_type;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_active;

    public function getId(): int
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

    public function getDiscountValue(): int
    {
        return $this->discount_value;
    }

    public function setDiscountValue(int $discount_value): static
    {
        $this->discount_value = $discount_value;

        return $this;
    }

    public function getDiscountType(): string
    {
        return $this->discount_type;
    }

    public function setDiscountType(string $discount_type): static
    {
        $this->discount_type = $discount_type;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }
}
