<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_REQUEST_RATE_LIMIT'),
    new SortKey('SECOND', ['second']),
)]
class TelegramBotGlobalRequestSecondRateLimit
{
    public function __construct(
        #[Attribute]
        private readonly int $second,
        #[Attribute]
        private readonly int $count = 0,
        #[Attribute('expire_at')]
        private ?DateTimeInterface $expireAt = null,
    )
    {
    }

    public function getSecond(): int
    {
        return $this->second;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
    }
}
