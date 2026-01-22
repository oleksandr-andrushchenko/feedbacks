<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

readonly class ClarityPersonDeclaration
{
    public function __construct(
        private string $name,
        private ?string $href = null,
        private ?string $year = null,
        private ?string $position = null,
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

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }
}