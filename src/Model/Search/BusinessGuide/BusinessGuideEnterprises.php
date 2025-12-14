<?php

declare(strict_types=1);

namespace App\Model\Search\BusinessGuide;

readonly class BusinessGuideEnterprises
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