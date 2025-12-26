<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Repository\Telegram\Bot\TelegramBotRepository;

trait TelegramBotRepositoryProviderTrait
{
    public function getTelegramBotRepository(): TelegramBotRepository
    {
        return static::getContainer()->get('app.telegram_bot_repository');
    }
}