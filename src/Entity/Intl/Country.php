<?php

declare(strict_types=1);

namespace App\Entity\Intl;

readonly class Country
{
    public function __construct(
        private string $code,
        private string $currency,
        private array $locales
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}