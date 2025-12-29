<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('USER_CONTACT_MESSAGE', ['id']),
    new SortKey('META'),
)]
class UserContactMessage implements Stringable
{
    #[Attribute('user_id')]
    private ?string $userId = null;
    #[Attribute('messenger_user_id')]
    private ?string $messengerUserId = null;
    #[Attribute('telegram_bot_id')]
    private ?string $telegramBotId = null;
    #[Attribute('created_at')]
    private ?DateTimeInterface $createdAt = null;

    public function __construct(
        #[Attribute]
        private readonly string $id,
        private ?MessengerUser $messengerUser,
        private User $user,
        #[Attribute]
        private readonly string $text,
        private ?TelegramBot $telegramBot,
    )
    {
        $this->userId = $this->user?->getId();
        $this->messengerUserId = $this->messengerUser?->getId();
        $this->telegramBotId = $this->telegramBot?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
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

    public function getText(): string
    {
        return $this->text;
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