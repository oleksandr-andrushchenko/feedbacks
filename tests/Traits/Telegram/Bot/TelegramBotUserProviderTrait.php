<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotUserProvider;

trait TelegramBotUserProviderTrait
{
    public function getTelegramBotUserProvider(): TelegramBotUserProvider
    {
        return static::getContainer()->get('app.telegram_bot_user_provider');
    }
}