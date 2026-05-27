<?php
declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramBotPaymentStatus: int
{
    case REQUEST_SENT = 0;
    case PRE_CHECKOUT_RECEIVED = 1;
    case SUCCESSFUL_PAYMENT_RECEIVED = 2;
}