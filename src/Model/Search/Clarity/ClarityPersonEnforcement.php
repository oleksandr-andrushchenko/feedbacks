<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

use DateTimeImmutable;
use DateTimeInterface;

readonly class ClarityPersonEnforcement
{
    public function __construct(
        private string $number,
        private ?DateTimeImmutable $openedAt = null,
        private ?string $collector = null,
        private ?string $debtor = null,
        private ?DateTimeImmutable $bornAt = null,
        private ?string $state = null,
    )
    {
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getOpenedAt(): ?DateTimeInterface
    {
        return $this->openedAt;
    }

    public function getCollector(): ?string
    {
        return $this->collector;
    }

    public function getDebtor(): ?string
    {
        return $this->debtor;
    }

    public function getBornAt(): ?DateTimeInterface
    {
        return $this->bornAt;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}