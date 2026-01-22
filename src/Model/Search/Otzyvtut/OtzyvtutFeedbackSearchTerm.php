<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvtut;

readonly class OtzyvtutFeedbackSearchTerm
{
    public function __construct(
        private string $name,
        private string $href,
        private ?string $photo = null,
        private ?string $category = null,
        private ?float $rating = null,
        private ?string $desc = null,
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

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}