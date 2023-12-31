<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Location;
use DateTimeInterface;

class User
{
    private ?string $locationLatitude;
    private ?string $locationLongitude;

    public function __construct(
        private string $id,
        private ?string $username = null,
        private ?string $name = null,
        private ?string $countryCode = null,
        ?Location $location = null,
        private ?string $level1RegionId = null,
        private ?string $localeCode = null,
        private ?string $currencyCode = null,
        private ?string $timezone = null,
        private ?string $phoneNumber = null,
        private ?string $email = null,
        private ?DateTimeInterface $subscriptionExpireAt = null,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
        private ?DateTimeInterface $purgedAt = null,
    )
    {
        $this->setLocation($location);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLocation(): ?Location
    {
        if ($this->locationLatitude === null || $this->locationLongitude === null) {
            return null;
        }

        return new Location((float) $this->locationLatitude, (float) $this->locationLongitude);
    }

    public function setLocation(?Location $location): self
    {
        if ($location === null) {
            $this->locationLatitude = null;
            $this->locationLongitude = null;
        } else {
            $this->locationLatitude = (string) $location->getLatitude();
            $this->locationLongitude = (string) $location->getLongitude();
        }

        return $this;
    }

    public function getLevel1RegionId(): ?string
    {
        return $this->level1RegionId;
    }

    public function setLevel1RegionId(?string $level1RegionId): self
    {
        $this->level1RegionId = $level1RegionId;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSubscriptionExpireAt(): ?DateTimeInterface
    {
        return $this->subscriptionExpireAt;
    }

    public function setSubscriptionExpireAt(?DateTimeInterface $subscriptionExpireAt): self
    {
        $this->subscriptionExpireAt = $subscriptionExpireAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPurgedAt(): ?DateTimeInterface
    {
        return $this->purgedAt;
    }

    public function setPurgedAt(?DateTimeInterface $purgedAt): self
    {
        $this->purgedAt = $purgedAt;

        return $this;
    }
}
