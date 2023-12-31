<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use DateTimeInterface;
use Stringable;

class FeedbackLookup implements Stringable
{
    public function __construct(
        private readonly string $id,
        private readonly User $user,
        private readonly MessengerUser $messengerUser,
        private readonly FeedbackSearchTerm $searchTerm,
        private readonly bool $hasActiveSubscription,
        private readonly ?string $countryCode = null,
        private readonly ?string $localeCode = null,
        private readonly ?TelegramBot $telegramBot = null,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSearchTerm(): FeedbackSearchTerm
    {
        return $this->searchTerm;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->hasActiveSubscription;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
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

    public function __toString(): string
    {
        return $this->getId();
    }
}
