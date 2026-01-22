<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

readonly class ClarityPersonEdr
{
    public function __construct(
        private string $name,
        private ?string $type = null,
        private ?string $href = null,
        private ?string $number = null,
        private ?bool $active = null,
        private ?string $address = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
}