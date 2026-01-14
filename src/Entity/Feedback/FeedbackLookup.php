<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('FEEDBACK_LOOKUP', ['id']),
    new SortKey('META'),
)]
class FeedbackLookup
{
    #[Attribute('search_term_id')]
    private ?string $searchTermId = null;

    public function __construct(
        #[Attribute('feedback_lookup_id')]
        private string $id,
        private ?SearchTerm $searchTerm = null,
        private ?User $user = null,
        #[Attribute('user_id')]
        private ?string $userId = null,
        #[Attribute('has_active_subscription')]
        private ?bool $hasActiveSubscription = null,
        #[Attribute('country_code')]
        private ?string $countryCode = null,
        #[Attribute('local_code')]
        private ?string $localeCode = null,
        private ?MessengerUser $messengerUser = null,
        private ?TelegramBot $telegramBot = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
        #[Attribute('telegram_bot_id')]
        private ?string $telegramBotId = null,
    )
    {
        $this->searchTermId = $this->searchTerm?->getId();
        $this->userId = $this->user?->getId();
        $this->countryCode = $this->user?->getCountryCode();
        $this->localeCode = $this->user?->getLocaleCode();
        $this->hasActiveSubscription = $this->user?->hasActiveSubscription() === true ? true : null;
        $this->messengerUserId ??= $this->messengerUser?->getId();
        $this->telegramBotId ??= $this->telegramBot?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->userId = $user?->getId();
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setMessengerUser(?MessengerUser $messengerUser): self
    {
        $this->messengerUser = $messengerUser;
        $this->messengerUserId = $messengerUser?->getId();
        return $this;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function setMessengerUserId(?string $messengerUserId): self
    {
        $this->messengerUserId = $messengerUserId;
        return $this;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }

    public function setSearchTermId(?string $searchTermId): self
    {
        $this->searchTermId = $searchTermId;
        return $this;
    }

    public function setSearchTerm(?SearchTerm $searchTerm): self
    {
        $this->searchTerm = $searchTerm;
        $this->searchTermId = $searchTerm?->getId();
        return $this;
    }

    public function getSearchTerm(): ?SearchTerm
    {
        return $this->searchTerm;
    }

    public function getSearchTermId(): ?string
    {
        return $this->searchTermId;
    }

    public function hasActiveSubscription(): ?bool
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

    public function setTelegramBot(?TelegramBot $telegramBot): self
    {
        $this->telegramBot = $telegramBot;
        $this->telegramBotId = $telegramBot?->getId();
        return $this;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function setTelegramBotId(?string $telegramBotId): self
    {
        $this->telegramBotId = $telegramBotId;
        return $this;
    }

    public function getTelegramBotId(): ?string
    {
        return $this->telegramBotId;
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
}
