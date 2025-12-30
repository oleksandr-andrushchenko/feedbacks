<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramBotPaymentMethodName;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_PAYMENT_METHOD', ['id']),
    new SortKey('META'),
)]
class TelegramBotPaymentMethod implements Stringable
{
    #[Attribute('deleted_at')]
    private ?DateTimeInterface $deletedAt = null;
    #[Attribute('telegram_bot_id')]
    private ?string $telegramBotId = null;

    public function __construct(
        #[Attribute('telegram_bot_payment_method_id')]
        private string $id,
        private readonly TelegramBot $telegramBot,
        #[Attribute]
        private readonly TelegramBotPaymentMethodName $name,
        #[Attribute]
        private readonly string $token,
        #[Attribute('currency_codes')]
        private readonly array $currencyCodes,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->telegramBotId = $this->telegramBot?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getTelegramBot(): TelegramBot
    {
        return $this->telegramBot;
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
        return $this->id;
    }
}
