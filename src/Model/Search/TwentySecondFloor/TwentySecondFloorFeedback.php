<?php

declare(strict_types=1);

namespace App\Model\Search\TwentySecondFloor;

use DateTimeInterface;

readonly class TwentySecondFloorFeedback
{
    public function __construct(
        private string $text,
        private ?string $header = null,
        private ?int $mark = null,
        private ?string $author = null,
        private ?DateTimeInterface $date = null
    )
    {
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function getMark(): ?int
    {
        return $this->mark;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }
}