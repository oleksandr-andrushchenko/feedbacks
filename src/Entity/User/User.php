<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Model\Location;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('USER', ['id']),
    new SortKey('META'),
)]
class User implements Stringable
{
    #[Attribute('location_latitude')]
    private ?string $locationLatitude;
    #[Attribute('location_longitude')]
    private ?string $locationLongitude;

    public function __construct(
        #[Attribute('user_id')]
        private string $id,
        #[Attribute]
        private ?string $username = null,
        #[Attribute]
        private ?string $name = null,
        #[Attribute('country_code')]
        private ?string $countryCode = null,
        ?Location $location = null,
        #[Attribute('level_1_region_id')]
        private ?string $level1RegionId = null,
        #[Attribute('locale_code')]
        private ?string $localeCode = null,
        #[Attribute('currency_code')]
        private ?string $currencyCode = null,
        #[Attribute('timezone')]
        private ?string $timezone = null,
        #[Attribute('phone_number')]
        private ?string $phoneNumber = null,
        #[Attribute('email')]
        private ?string $email = null,
        #[Attribute('subscription_expire_at')]
        private ?DateTimeInterface $subscriptionExpireAt = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('updated_at')]
        private ?DateTimeInterface $updatedAt = null,
        #[Attribute('purged_at')]
        private ?DateTimeInterface $purgedAt = null,
    )
    {
        $this->setLocation($location);
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
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

    public function hasActiveSubscription(): bool
    {
        if ($this->getSubscriptionExpireAt() === null) {
            return false;
        }

        return new DateTimeImmutable() < $this->getSubscriptionExpireAt();
    }

    public function hasSubscription(): bool
    {
        return $this->getSubscriptionExpireAt() !== null;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
