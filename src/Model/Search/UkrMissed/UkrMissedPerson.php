<?php

declare(strict_types=1);

namespace App\Model\Search\UkrMissed;

use DateTimeInterface;

readonly class UkrMissedPerson
{
    public function __construct(
        private ?string $surname = null,
        private ?string $name = null,
        private ?string $middleName = null,
        private ?string $sex = null,
        private ?DateTimeInterface $birthday = null,
        private ?string $photo = null,
        private ?string $category = null,
        private ?bool $disappeared = null,
        private ?array $articles = null,
        private ?DateTimeInterface $date = null,
        private ?string $organ = null,
        private ?string $precaution = null,
        private ?string $address = null,
    )
    {
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function getBirthday(): ?DateTimeInterface
    {
        return $this->birthday;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getDisappeared(): ?bool
    {
        return $this->disappeared;
    }

    public function getArticles(): ?array
    {
        return $this->articles;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function getOrgan(): ?string
    {
        return $this->organ;
    }

    public function getPrecaution(): ?string
    {
        return $this->precaution;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
}