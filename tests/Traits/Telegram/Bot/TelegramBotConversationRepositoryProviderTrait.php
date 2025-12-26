<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Repository\Telegram\Bot\TelegramBotConversationRepository;

trait TelegramBotConversationRepositoryProviderTrait
{
    public function getTelegramBotConversationRepository(): TelegramBotConversationRepository
    {
        return static::getContainer()->get('app.telegram_bot_conversation_repository');
    }
}