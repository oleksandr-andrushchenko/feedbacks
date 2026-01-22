<?php

declare(strict_types=1);

namespace App\Model\Search\Otzyvua;

readonly class OtzyvuaFeedbacks
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