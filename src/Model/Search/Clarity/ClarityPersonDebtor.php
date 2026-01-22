<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

use DateTimeInterface;

readonly class ClarityPersonDebtor
{
    public function __construct(
        private string $name,
        private ?DateTimeInterface $bornAt = null,
        private ?string $category = null,
        private ?DateTimeInterface $actualAt = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBornAt(): ?DateTimeInterface
    {
        return $this->bornAt;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getActualAt(): ?DateTimeInterface
    {
        return $this->actualAt;
    }
}