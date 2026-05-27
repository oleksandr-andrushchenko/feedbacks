<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;

trait TelegramBotPaymentRepositoryProviderTrait
{
    public function getTelegramBotPaymentRepository(): TelegramBotPaymentRepository
    {
        return static::getContainer()->get('app.telegram_bot_payment_repository');
    }
}