<?php

declare(strict_types=1);

namespace App\Model;

readonly class PersonName
{
    public function __construct(
        private string $raw,
        private ?string $first = null,
        private ?string $last = null,
        private ?string $middle = null,
        private ?string $patronymic = null,
        private ?string $formatted = null,
        private ?string $gender = null,
        private ?string $locale = null,
    )
    {
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getFirst(): ?string
    {
        return $this->first;
    }

    public function getLast(): ?string
    {
        return $this->last;
    }

    public function getMiddle(): ?string
    {
        return $this->middle;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function getFormatted(): ?string
    {
        return $this->formatted;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getComponentsCount(): int
    {
        return count(array_filter([$this->first, $this->last, $this->middle, $this->patronymic]));
    }
}