<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotAwareHelper;

trait TelegramBotAwareHelperProviderTrait
{
    public function getTelegramBotAwareHelper(): TelegramBotAwareHelper
    {
        return static::getContainer()->get('app.telegram_bot_aware_helper');
    }
}