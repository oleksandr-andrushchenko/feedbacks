<?php

declare(strict_types=1);

namespace App\Model\Intl;

readonly class Locale
{
    public function __construct(
        private string $code,
        private string $flag,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}