<?php
declare(strict_types=1);

namespace App\Transfer\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramBotPaymentMethodName;

readonly class TelegramBotPaymentMethodTransfer
{
    public function __construct(
        private TelegramBot $bot,
        private TelegramBotPaymentMethodName $name,
        private string $token,
        private array $currencies,
    )
    {
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getName(): TelegramBotPaymentMethodName
    {
        return $this->name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}