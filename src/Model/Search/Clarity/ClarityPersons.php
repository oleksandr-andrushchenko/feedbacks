<?php

declare(strict_types=1);

namespace App\Model\Search\Clarity;

readonly class ClarityPersons
{
    public function __construct(
        private array $items = []
    )
    {
    }

    /**
     * @return ClarityPerson[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}