<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

use DateTimeInterface;

readonly class ClarityPersonSecurity
{
    public function __construct(
        private string $name,
        private ?DateTimeInterface $bornAt = null,
        private ?string $category = null,
        private ?string $region = null,
        private ?DateTimeInterface $absentAt = null,
        private ?bool $archive = null,
        private ?string $accusation = null,
        private ?string $precaution = null,
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getAbsentAt(): ?DateTimeInterface
    {
        return $this->absentAt;
    }

    public function getArchive(): ?bool
    {
        return $this->archive;
    }

    public function getAccusation(): ?string
    {
        return $this->accusation;
    }

    public function getPrecaution(): ?string
    {
        return $this->precaution;
    }
}