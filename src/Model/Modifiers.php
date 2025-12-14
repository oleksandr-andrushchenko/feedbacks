<?php

declare(strict_types=1);

namespace App\Model;

class Modifiers
{
    public function __construct(
        private array $items = [],
    )
    {
    }

    public function add(callable $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function apply($target): ?string
    {
        foreach ($this->items as $item) {
            $target = $item($target);
        }

        return $target;
    }
}