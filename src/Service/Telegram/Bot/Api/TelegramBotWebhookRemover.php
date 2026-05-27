<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;

class TelegramBotWebhookRemover
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
    )
    {
    }

    public function removeTelegramWebhook(TelegramBot $botEntity): void
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);

        $bot->deleteWebhook();

        $bot->getEntity()->setWebhookSynced(false);
    }
}