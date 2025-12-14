<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

readonly class ClarityPersonCourt
{
    public function __construct(
        private string $number,
        private ?string $state = null,
        private ?string $side = null,
        private ?string $desc = null,
        private ?string $place = null,
    )
    {
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }
}