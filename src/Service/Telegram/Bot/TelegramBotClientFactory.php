<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use Longman\TelegramBot\Telegram as TelegramClient;

class TelegramBotClientFactory
{
    public function createTelegramClient(TelegramBot $bot): TelegramClient
    {
        return new TelegramClient(
            $bot->getToken(),
            $bot->getUsername(),
        );
    }
}
