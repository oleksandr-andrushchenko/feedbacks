<?php

declare(strict_types=1);

namespace App\Model\Search\CleanTalk;

use DateTimeInterface;

readonly class CleanTalkEmail
{
    public function __construct(
        private string $address,
        private string $href,
        private int $attackedSites,
        private bool $blacklisted,
        private bool $real,
        private bool $disposable,
        private ?DateTimeInterface $lastUpdate = null
    )
    {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getAttackedSites(): int
    {
        return $this->attackedSites;
    }

    public function isBlacklisted(): bool
    {
        return $this->blacklisted;
    }

    public function isReal(): bool
    {
        return $this->real;
    }

    public function isDisposable(): bool
    {
        return $this->disposable;
    }

    public function getLastUpdate(): ?DateTimeInterface
    {
        return $this->lastUpdate;
    }
}