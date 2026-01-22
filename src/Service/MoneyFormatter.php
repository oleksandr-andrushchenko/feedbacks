<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Money;
use App\Service\Intl\CurrencyProvider;

class MoneyFormatter
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
    )
    {
    }

    public function formatMoney(Money $money, bool $native = false): string
    {
        $currency = $this->currencyProvider->getCurrency($money->getCurrency());

        $amount = number_format($money->getAmount(), 2, ',', '');
        $space = $currency->isSpaceBetween() ? ' ' : '';
        $symbol = $native ? $currency->getNative() : $currency->getSymbol();

        if ($currency->isSymbolLeft()) {
            return $symbol . $space . $amount;
        }

        return $amount . $space . $symbol;
    }

    public function formatMoneyAsTelegramButton(Money $money): string
    {
        $currency = $this->currencyProvider->getCurrency($money->getCurrency());

        $amount = number_format($money->getAmount(), 2, ',', ' ');
        $symbol = $currency->getCode();

        return $amount . $symbol;
    }
}