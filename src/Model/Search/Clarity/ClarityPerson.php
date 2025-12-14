<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

readonly class ClarityPerson
{
    public function __construct(
        private string $name,
        private ?string $href = null,
        private ?int $count = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}