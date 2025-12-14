<?php

declare(strict_types=1);

namespace App\Model\Address;

readonly class AddressComponent
{
    public function __construct(
        private string $shortName,
        private string $longName,
    )
    {
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getLongName(): string
    {
        return $this->longName;
    }
}
