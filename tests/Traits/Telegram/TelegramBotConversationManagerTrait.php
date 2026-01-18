<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;

trait TelegramBotConversationManagerTrait
{
    public function getTelegramBotConversationManager(): TelegramBotConversationManager
    {
        return static::getContainer()->get('app.telegram_bot_conversation_manager');
    }
}