<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Tests\Fake\Service\Telegram\Bot\Api\FakeTelegramBotMessageSender;

trait TelegramBotMessageSenderProviderTrait
{
    public function getTelegramBotMessageSender(): FakeTelegramBotMessageSender
    {
        return static::getContainer()->get('app.telegram_bot_message_sender');
    }
}