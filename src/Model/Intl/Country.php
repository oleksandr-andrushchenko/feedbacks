<?php

declare(strict_types=1);

namespace App\Model\Intl;

readonly class Country
{
    public function __construct(
        private string $code,
        private string $currency,
        private array $locales,
        private string $phone,
        private array $timezones,
        private bool $level1RegionsDumped,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCurrencyCode(): string
    {
        return $this->currency;
    }

    public function getLocaleCodes(): array
    {
        return $this->locales;
    }

    public function getPhoneCode(): string
    {
        return $this->phone;
    }

    public function getTimezones(): array
    {
        return $this->timezones;
    }

    public function level1RegionsDumped(): bool
    {
        return $this->level1RegionsDumped;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}