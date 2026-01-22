<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvua;

use DateTimeInterface;

readonly class OtzyvuaFeedback
{
    public function __construct(
        private string $title,
        private string $href,
        private ?int $rating = null,
        private ?string $authorName = null,
        private ?string $authorHref = null,
        private ?string $description = null,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function getAuthorHref(): ?string
    {
        return $this->authorHref;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }
}