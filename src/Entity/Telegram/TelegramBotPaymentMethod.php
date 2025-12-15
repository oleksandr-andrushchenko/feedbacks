<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramBotPaymentMethodName;
use DateTimeInterface;
use Stringable;

class TelegramBotPaymentMethod implements Stringable
{
    public function __construct(
        private readonly TelegramBot $bot,
        private readonly TelegramBotPaymentMethodName $name,
        private readonly string $token,
        private readonly array $currencyCodes,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $deletedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getName(): TelegramBotPaymentMethodName
    {
        return $this->name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCurrencyCodes(): array
    {
        return $this->currencyCodes;
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

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
