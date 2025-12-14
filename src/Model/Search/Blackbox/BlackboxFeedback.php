<?php

declare(strict_types=1);

namespace App\Model\Search\Blackbox;

use DateTimeInterface;

readonly class BlackboxFeedback
{
    public function __construct(
        private string $name,
        private string $href,
        private string $phone,
        private ?string $phoneFormatted = null,
        private ?string $comment = null,
        private ?DateTimeInterface $date = null,
        private ?string $city = null,
        private ?string $warehouse = null,
        private ?string $cost = null,
        private ?string $type = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPhoneFormatted(): ?string
    {
        return $this->phoneFormatted;
    }


    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getWarehouse(): ?string
    {
        return $this->warehouse;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}