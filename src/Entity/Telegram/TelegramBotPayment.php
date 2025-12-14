<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Telegram\TelegramBotPaymentStatus;
use App\Model\Money;
use DateTimeInterface;
use Stringable;

class TelegramBotPayment implements Stringable
{
    private readonly float $priceAmount;
    private readonly string $priceCurrency;

    public function __construct(
        private readonly string $id,
        private readonly MessengerUser $messengerUser,
        private readonly string $chatId,
        private readonly TelegramBotPaymentMethod $method,
        private readonly string $purpose,
        Money $price,
        private readonly array $payload,
        private readonly TelegramBot $bot,
        private ?array $preCheckoutQuery = null,
        private ?array $successfulPayment = null,
        private ?TelegramBotPaymentStatus $status = TelegramBotPaymentStatus::REQUEST_SENT,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
    )
    {
        $this->priceAmount = $price->getAmount();
        $this->priceCurrency = $price->getCurrency();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function getMethod(): TelegramBotPaymentMethod
    {
        return $this->method;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getPrice(): Money
    {
        return new Money($this->priceAmount, $this->priceCurrency);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getPreCheckoutQuery(): ?array
    {
        return $this->preCheckoutQuery;
    }

    public function setPreCheckoutQuery(?array $preCheckoutQuery): self
    {
        $this->preCheckoutQuery = $preCheckoutQuery;

        return $this;
    }

    public function getSuccessfulPayment(): ?array
    {
        return $this->successfulPayment;
    }

    public function setSuccessfulPayment(?array $successfulPayment): self
    {
        $this->successfulPayment = $successfulPayment;

        return $this;
    }

    public function getStatus(): TelegramBotPaymentStatus
    {
        return $this->status;
    }

    public function setStatus(TelegramBotPaymentStatus $status): self
    {
        $this->status = $status;

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

    public function __toString(): string
    {
        return $this->getId();
    }
}
