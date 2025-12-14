<?php

declare(strict_types=1);

namespace App\Model\Search\CleanTalk;

readonly class CleanTalkEmails
{
    public function __construct(
        private array $items = []
    )
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}