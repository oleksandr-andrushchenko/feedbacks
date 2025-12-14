<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('TELEGRAM_BOT_REQUEST_RATE_LIMIT', ['chatId']),
    new SortKey('MINUTE', ['minute']),
)]
class TelegramBotChatRequestMinuteRateLimit
{
    public function __construct(
        #[Attribute('chat_id')]
        private readonly int $chatId,
        #[Attribute]
        private readonly int $minute,
        #[Attribute]
        private readonly int $count = 0,
        #[Attribute('expire_at')]
        private ?DateTimeInterface $expireAt = null,
    )
    {
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getMinute(): int
    {
        return $this->minute;
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
