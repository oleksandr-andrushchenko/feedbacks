<?php

declare(strict_types=1);

namespace App\Transfer\Messenger;

use App\Enum\Messenger\Messenger;

readonly class MessengerUserTransfer
{
    public function __construct(
        private Messenger $messenger,
        private string $id,
        private ?string $username = null,
        private ?string $name = null,
        private ?string $countryCode = null,
        private ?string $localeCode = null,
        private ?string $currencyCode = null,
        private ?string $timezone = null,
        private ?string $telegramBotId = null,
    )
    {
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getTelegramBotId(): ?string
    {
        return $this->telegramBotId;
    }
}