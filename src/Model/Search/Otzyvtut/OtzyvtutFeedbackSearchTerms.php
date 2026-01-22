<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvtut;

readonly class OtzyvtutFeedbackSearchTerms
{
    public function __construct(
        private array $items
    )
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}