<?php

declare(strict_types=1);

namespace App\Model\Search\BusinessGuide;

readonly class BusinessGuideEnterprise
{
    public function __construct(
        private string $name,
        private string $href,
        private ?string $country = null,
        private ?string $phone = null,
        private ?string $ceo = null,
        private ?array $sectors = null,
        private ?string $desc = null,
        private ?string $address = null,
        private ?string $number = null,
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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCeo(): ?string
    {
        return $this->ceo;
    }

    public function getSectors(): ?array
    {
        return $this->sectors;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }
}