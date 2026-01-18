<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_CONVERSATION', ['hash']),
    new SortKey('META', ['createdAt'])),
]
class TelegramBotConversation implements Stringable
{
    private ?string $id = null;
    #[Attribute('expire_at')]
    private ?DateTimeInterface $expireAt = null;
    #[Attribute('created_at')]
    private ?DateTimeInterface $createdAt = null;
    #[Attribute('updated_at')]
    private ?DateTimeInterface $updatedAt = null;
    #[Attribute('deleted_at')]
    private ?DateTimeInterface $deletedAt = null;

    public function __construct(
        #[Attribute]
        private readonly string $hash,
        #[Attribute('messenger_user_id')]
        private readonly string $messengerUserId,
        #[Attribute('chat_id')]
        private readonly string $chatId,
        #[Attribute('telegram_bot_id')]
        private string $telegramBotId,
        #[Attribute]
        private readonly string $class,
        #[Attribute]
        private ?array $state
    )
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->getHash();
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMessengerUserId(): string
    {
        return $this->messengerUserId;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setTelegramBotId(?string $telegramBotId): self
    {
        $this->telegramBotId = $telegramBotId;
        return $this;
    }

    public function getTelegramBotId(): string
    {
        return $this->telegramBotId;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function setState(?array $state): self
    {
        $this->state = $state;

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

    public function setExpireAt(?DateTimeInterface $expireAt): self
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    public function getExpireAt(): ?DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
