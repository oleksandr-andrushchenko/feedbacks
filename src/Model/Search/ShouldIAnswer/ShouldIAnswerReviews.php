<?php

declare(strict_types=1);

namespace App\Model\Search\ShouldIAnswer;

readonly class ShouldIAnswerReviews
{
    public function __construct(
        private string $header,
        private string $info,
        private ?int $score = null,
        private array $items = []
    )
    {
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    /**
     * @return ShouldIAnswerReview[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}