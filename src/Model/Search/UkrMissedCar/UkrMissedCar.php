<?php

declare(strict_types=1);

namespace App\Model\Search\UkrMissedCar;

readonly class UkrMissedCar
{
    public function __construct(
        private string $carNumber,
        private ?string $region = null,
        private ?string $model = null,
        private ?string $chassisNumber = null,
        private ?string $bodyNumber = null,
        private ?string $color = null,
    )
    {
    }

    public function getCarNumber(): string
    {
        return $this->carNumber;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getChassisNumber(): ?string
    {
        return $this->chassisNumber;
    }

    public function getBodyNumber(): ?string
    {
        return $this->bodyNumber;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}