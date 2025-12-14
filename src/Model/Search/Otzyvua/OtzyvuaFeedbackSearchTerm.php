<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvua;

readonly class OtzyvuaFeedbackSearchTerm
{
    public function __construct(
        private string $name,
        private string $href,
        private ?string $category = null,
        private ?float $rating = null,
        private ?int $count = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}