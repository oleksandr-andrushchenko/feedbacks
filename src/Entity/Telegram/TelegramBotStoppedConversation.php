<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;

class TelegramBotStoppedConversation
{
    public function __construct(
        private readonly string $messengerUserId,
        private readonly string $chatId,
        private readonly string $botId,
        private readonly string $class,
        private readonly array $state,
        private readonly DateTimeInterface $startedAt,
        private ?DateTimeInterface $createdAt = null,
        private ?string $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessengerUserId(): string
    {
        return $this->messengerUserId;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function getBotId(): string
    {
        return $this->botId;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function getStartedAt(): DateTimeInterface
    {
        return $this->startedAt;
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
