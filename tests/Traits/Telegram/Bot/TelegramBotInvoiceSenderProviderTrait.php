<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Tests\Fake\Service\Telegram\Bot\Api\FakeTelegramBotInvoiceSender;

trait TelegramBotInvoiceSenderProviderTrait
{
    public function getTelegramBotInvoiceSender(): FakeTelegramBotInvoiceSender
    {
        return static::getContainer()->get('app.telegram_bot_invoice_sender');
    }
}