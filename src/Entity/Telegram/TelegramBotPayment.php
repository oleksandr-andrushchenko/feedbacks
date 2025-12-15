<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Telegram\TelegramBotPaymentStatus;
use App\Model\Money;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_PAYMENT', ['id']),
    new SortKey('META'),
)]
class TelegramBotPayment implements Stringable
{
    #[Attribute('price_amount')]
    private readonly float $priceAmount;
    #[Attribute('price_currency')]
    private readonly string $priceCurrency;

    public function __construct(
        #[Attribute('telegram_bot_payment_id')]
        private readonly string $id,
        private readonly MessengerUser $messengerUser,
        #[Attribute('chat_id')]
        private readonly string $chatId,
        #[Attribute]
        private readonly TelegramBotPaymentMethod $method,
        #[Attribute]
        private readonly string $purpose,
        Money $price,
        #[Attribute]
        private readonly array $payload,
        private readonly TelegramBot $telegramBot,
        #[Attribute('pre_checkout_query')]
        private ?array $preCheckoutQuery = null,
        #[Attribute('successful_payment')]
        private ?array $successfulPayment = null,
        #[Attribute]
        private ?TelegramBotPaymentStatus $status = TelegramBotPaymentStatus::REQUEST_SENT,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('updated_at')]
        private ?DateTimeInterface $updatedAt = null,
        #[Attribute('telegram_bot_id')]
        private ?string $telegramBotId = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
    )
    {
        $this->priceAmount = $price->getAmount();
        $this->priceCurrency = $price->getCurrency();
        $this->telegramBotId = $this->telegramBot?->getId();
        $this->messengerUserId = $this->messengerUser?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
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

    public function getTelegramBot(): TelegramBot
    {
        return $this->telegramBot;
    }

    public function getTelegramBotId(): ?string
    {
        return $this->telegramBotId;
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
