<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotChatProvider;

trait TelegramBotChatProviderTrait
{
    public function getTelegramBotChatProvider(): TelegramBotChatProvider
    {
        return static::getContainer()->get('app.telegram_bot_chat_provider');
    }
}