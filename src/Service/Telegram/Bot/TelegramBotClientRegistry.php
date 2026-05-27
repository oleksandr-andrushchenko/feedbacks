<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use Longman\TelegramBot\Telegram as TelegramClient;

class TelegramBotClientRegistry
{
    public function __construct(
        private readonly TelegramBotClientFactory $telegramBotClientFactory,
        private ?array $cache = null,
    )
    {
        $this->cache = [];
    }

    public function getTelegramClient(TelegramBot $bot): TelegramClient
    {
        $key = $bot->getUsername();

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->telegramBotClientFactory->createTelegramClient($bot);
    }
}