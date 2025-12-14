<?php

declare(strict_types=1);

namespace App\Model\Search\UkrWantedPerson;

use DateTimeInterface;

readonly class UkrWantedPerson
{
    public function __construct(
        private string $ukrSurname,
        private string $ukrName,
        private ?string $ukrPatronymic = null,
        private ?string $rusSurname = null,
        private ?string $rusName = null,
        private ?string $rusPatronymic = null,
        private ?string $gender = null,
        private ?string $region = null,
        private ?DateTimeInterface $bornAt = null,
        private ?string $photo = null,
        private ?string $category = null,
        private ?DateTimeInterface $absentAt = null,
        private ?string $absentPlace = null,
        private ?string $href = null,
        private ?string $precaution = null,
        private ?string $codexArticle = null,
        private ?string $callTo = null,
    )
    {
    }

    public function getUkrSurname(): string
    {
        return $this->ukrSurname;
    }

    public function getUkrName(): string
    {
        return $this->ukrName;
    }

    public function getUkrPatronymic(): ?string
    {
        return $this->ukrPatronymic;
    }

    public function getRusSurname(): ?string
    {
        return $this->rusSurname;
    }

    public function getRusName(): ?string
    {
        return $this->rusName;
    }

    public function getRusPatronymic(): ?string
    {
        return $this->rusPatronymic;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getBornAt(): ?DateTimeInterface
    {
        return $this->bornAt;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getAbsentAt(): ?DateTimeInterface
    {
        return $this->absentAt;
    }

    public function getAbsentPlace(): ?string
    {
        return $this->absentPlace;
    }

    public function getPrecaution(): ?string
    {
        return $this->precaution;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getCodexArticle(): ?string
    {
        return $this->codexArticle;
    }

    public function getCallTo(): ?string
    {
        return $this->callTo;
    }
}