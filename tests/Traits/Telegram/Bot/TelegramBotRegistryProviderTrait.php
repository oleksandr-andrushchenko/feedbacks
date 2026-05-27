<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotRegistry;

trait TelegramBotRegistryProviderTrait
{
    public function getTelegramBotRegistry(): TelegramBotRegistry
    {
        return static::getContainer()->get('app.telegram_bot_registry');
    }
}