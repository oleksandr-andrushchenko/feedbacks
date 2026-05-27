<?php
declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramBotPaymentMethodName: int
{
    case liqpay = 0;
    case portmone = 1;
    case sberbank = 2;
    case stripe = 3;

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $enum) {
            if ($enum->name === $name) {
                return $enum;
            }
        }

        return null;
    }
}
