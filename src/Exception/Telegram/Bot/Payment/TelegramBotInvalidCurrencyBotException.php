<?php
declare(strict_types=1);

namespace App\Exception\Telegram\Bot\Payment;

use App\Exception\Telegram\Bot\TelegramBotException;
use Throwable;

class TelegramBotInvalidCurrencyBotException extends TelegramBotException
{
    public function __construct(private readonly string $currency, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid "%s" telegram bot currency', $currency), $code, $previous);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}