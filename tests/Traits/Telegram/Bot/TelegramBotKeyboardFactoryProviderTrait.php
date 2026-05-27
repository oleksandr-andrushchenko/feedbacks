<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotKeyboardFactory;

trait TelegramBotKeyboardFactoryProviderTrait
{
    public function getTelegramBotKeyboardFactory(): TelegramBotKeyboardFactory
    {
        return static::getContainer()->get('app.telegram_bot_keyboard_factory');
    }
}