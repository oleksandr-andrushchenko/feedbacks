<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_UPDATE', ['id']),
    new SortKey('META'),
)]
class TelegramBotUpdate
{
    public function __construct(
        #[Attribute('telegram_bot_update_id')]
        private readonly string $id,
        #[Attribute]
        private readonly array $data,
        private readonly TelegramBot $bot,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('telegram_bot_id')]
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
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

    public function getBotId(): string
    {
        return $this->botId;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
    }
}
