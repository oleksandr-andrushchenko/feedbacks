<?php

declare(strict_types=1);

namespace App\Model\Intl;

readonly class Currency
{
    public function __construct(
        private string $code,
        private float $rate,
        private ?int $exp = 2,
        private ?string $symbol = null,
        private ?string $native = null,
        private ?bool $symbolLeft = null,
        private ?bool $spaceBetween = null,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getExp(): ?int
    {
        return $this->exp;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function getNative(): ?string
    {
        return $this->native;
    }

    public function isSymbolLeft(): ?bool
    {
        return $this->symbolLeft;
    }

    public function isSpaceBetween(): ?bool
    {
        return $this->spaceBetween;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}