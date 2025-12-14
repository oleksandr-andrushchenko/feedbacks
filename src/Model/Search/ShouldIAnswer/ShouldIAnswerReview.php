<?php

declare(strict_types=1);

namespace App\Model\Search\ShouldIAnswer;

use DateTimeInterface;

readonly class ShouldIAnswerReview
{
    public function __construct(
        private string $name,
        private string $author,
        private int $rating,
        private DateTimeInterface $datePublished,
        private ?string $description = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getDatePublished(): DateTimeInterface
    {
        return $this->datePublished;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}