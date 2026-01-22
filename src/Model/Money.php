<?php

declare(strict_types=1);

namespace App\Model;

readonly class Money
{
    public function __construct(
        private float|string $amount,
        private string $currency
    )
    {
    }

    public function getAmount(): float|string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
