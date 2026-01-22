<?php

declare(strict_types=1);

namespace App\Transfer\Telegram;

use App\Entity\Intl\Level1Region;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Model\Intl\Country;
use App\Model\Intl\Locale;

class TelegramChannelTransfer
{
    public function __construct(
        private readonly string $username,
        private ?TelegramBotGroupName $group = null,
        private bool $groupPassed = false,
        private ?string $name = null,
        private bool $namePassed = false,
        private ?Country $country = null,
        private bool $countryPassed = false,
        private ?Locale $locale = null,
        private bool $localePassed = false,
        private ?Level1Region $level1Region = null,
        private bool $level1RegionPassed = false,
        private ?string $chatId = null,
        private bool $chatIdPassed = false,
        private ?bool $primary = null,
        private bool $primaryPassed = false,
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setGroup(TelegramBotGroupName $group): self
    {
        $this->group = $group;
        $this->groupPassed = true;

        return $this;
    }

    public function groupPassed(): bool
    {
        return $this->groupPassed;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->namePassed = true;

        return $this;
    }

    public function namePassed(): bool
    {
        return $this->namePassed;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): self
    {
        $this->country = $country;
        $this->countryPassed = true;

        return $this;
    }

    public function countryPassed(): bool
    {
        return $this->countryPassed;
    }


    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    public function setLocale(?Locale $locale): self
    {
        $this->locale = $locale;
        $this->localePassed = true;

        return $this;
    }

    public function localePassed(): bool
    {
        return $this->localePassed;
    }

    public function getLevel1Region(): ?Level1Region
    {
        return $this->level1Region;
    }

    public function setLevel1Region(?Level1Region $level1Region): self
    {
        $this->level1Region = $level1Region;
        $this->level1RegionPassed = true;

        return $this;
    }

    public function level1RegionPassed(): bool
    {
        return $this->level1RegionPassed;
    }

    public function getGroup(): ?TelegramBotGroupName
    {
        return $this->group;
    }

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(?string $chatId): self
    {
        $this->chatId = $chatId;
        $this->chatIdPassed = true;

        return $this;
    }

    public function chatIdPassed(): bool
    {
        return $this->chatIdPassed;
    }

    public function primary(): ?bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;
        $this->primaryPassed = true;

        return $this;
    }

    public function primaryPassed(): bool
    {
        return $this->primaryPassed;
    }
}