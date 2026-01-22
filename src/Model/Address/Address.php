<?php

declare(strict_types=1);

namespace App\Model\Address;

readonly class Address
{
    public function __construct(
        private string $country,
        private string $administrativeAreaLevel1,
    )
    {
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getAdministrativeAreaLevel1(): string
    {
        return $this->administrativeAreaLevel1;
    }
}
