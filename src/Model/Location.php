<?php

declare(strict_types=1);

namespace App\Model;

readonly class Location
{
    public function __construct(
        private float|string $latitude,
        private float|string $longitude
    )
    {
    }

    public function getLatitude(): float|string
    {
        return $this->latitude;
    }

    public function getLongitude(): float|string
    {
        return $this->longitude;
    }
}
