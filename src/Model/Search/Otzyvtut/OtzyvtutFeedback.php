<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvtut;

use DateTimeInterface;

readonly class OtzyvtutFeedback
{
    public function __construct(
        private string $title,
        private int $rate,
        private ?string $text = null,
        private ?string $pluses = null,
        private ?string $minuses = null,
        private ?string $companyTitle = null,
        private ?string $author = null,
        private ?DateTimeInterface $dateAdded = null,
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getPluses(): ?string
    {
        return $this->pluses;
    }

    public function getMinuses(): ?string
    {
        return $this->minuses;
    }

    public function getCompanyTitle(): ?string
    {
        return $this->companyTitle;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getDateAdded(): ?DateTimeInterface
    {
        return $this->dateAdded;
    }
}