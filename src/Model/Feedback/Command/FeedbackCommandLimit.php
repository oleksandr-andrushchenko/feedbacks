<?php

declare(strict_types=1);

namespace App\Model\Feedback\Command;

readonly class FeedbackCommandLimit
{
    public function __construct(
        private string $period,
        private int $count
    )
    {
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
