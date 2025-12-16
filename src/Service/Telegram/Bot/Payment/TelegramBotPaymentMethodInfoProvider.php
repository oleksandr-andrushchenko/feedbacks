<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Payment;

use App\Entity\Telegram\TelegramBotPaymentMethod;

class TelegramBotPaymentMethodInfoProvider
{
    public function getTelegramPaymentInfo(TelegramBotPaymentMethod $paymentMethod): array
    {
        return [
            'bot' => $paymentMethod->getTelegramBot()->getUsername(),
            'name' => $paymentMethod->getName()->name,
            'currencies' => join(', ', $paymentMethod->getCurrencyCodes()),
        ];
    }
}