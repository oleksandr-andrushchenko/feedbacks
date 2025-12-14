<?php

declare(strict_types=1);

namespace App\Model\Search\UkrCorrupt;

use DateTimeInterface;

readonly class UkrCorruptPerson
{
    public function __construct(
        private ?string $punishmentType = null,
        private ?string $entityType = null,
        private ?string $lastName = null,
        private ?string $firstName = null,
        private ?string $patronymic = null,
        private ?string $offenseName = null,
        private ?string $punishment = null,
        private ?string $courtCaseNumber = null,
        private ?DateTimeInterface $sentenceDate = null,
        private ?DateTimeInterface $punishmentStart = null,
        private ?string $courtName = null,
        private ?array $codexArticles = null,
    )
    {
    }

    public function getPunishmentType(): ?string
    {
        return $this->punishmentType;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function getOffenseName(): ?string
    {
        return $this->offenseName;
    }

    public function getPunishment(): ?string
    {
        return $this->punishment;
    }

    public function getCourtCaseNumber(): ?string
    {
        return $this->courtCaseNumber;
    }

    public function getSentenceDate(): ?DateTimeInterface
    {
        return $this->sentenceDate;
    }

    public function getPunishmentStart(): ?DateTimeInterface
    {
        return $this->punishmentStart;
    }

    public function getCourtName(): ?string
    {
        return $this->courtName;
    }

    public function getCodexArticles(): ?array
    {
        return $this->codexArticles;
    }
}