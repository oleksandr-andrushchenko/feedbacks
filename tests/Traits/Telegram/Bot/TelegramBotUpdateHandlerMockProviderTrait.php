<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotUpdateHandler;
use PHPUnit\Framework\MockObject\MockObject;

trait TelegramBotUpdateHandlerMockProviderTrait
{
    public function getTelegramBotUpdateHandlerMock(bool $replace = true): TelegramBotUpdateHandler|MockObject
    {
        $mock = $this->createMock(TelegramBotUpdateHandler::class);

        if ($replace) {
            static::getContainer()->set('app.telegram_bot_update_handler', $mock);
        }

        return $mock;
    }
}