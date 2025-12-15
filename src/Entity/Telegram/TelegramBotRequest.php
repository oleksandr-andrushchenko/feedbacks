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
    new PartitionKey('TELEGRAM_BOT_REQUEST', ['id']),
    new SortKey('META'),
)]
class TelegramBotRequest implements Stringable
{
    public function __construct(
        #[Attribute('telegram_bot_request_id')]
        private string $id,
        #[Attribute]
        private readonly string $method,
        #[Attribute('chat_id')]
        private readonly null|int|string $chatId,
        #[Attribute]
        private readonly array $data,
        private readonly TelegramBot $bot,
        #[Attribute]
        private ?array $response = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('bot_id')]
        private ?string $botId = null,
        #[Attribute('expire_at')]
        private ?DateTimeInterface $expireAt = null,
    )
    {
        $this->botId = $this->bot->getId();

        if ($this->expireAt === null) {
            $this->expireAt = (new DateTimeImmutable())->setTimestamp(time() + 24 * 60 * 60);
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getChatId(): null|int|string
    {
        return $this->chatId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setResponse(array $response = null): self
    {
        $this->response = $response;

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

    public function getBotId(): ?string
    {
        return $this->botId;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
