<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Model\Intl\Currency;

class TelegramBotCurrenciesProvider implements CurrenciesProviderInterface
{
    public function getCurrencies(): ?array
    {
        $content = file_get_contents('https://core.telegram.org/bots/payments/currencies.json');
        $data = json_decode($content, true);

        unset($content);

        if (!is_array($data)) {
            return null;
        }

        $currencies = [];

        foreach ($data as $currency) {
            if (!isset($currency['code'], $currency['exp'], $currency['min_amount'])) {
                continue;
            }

            $code = $currency['code'];
            $symbol = $currency['symbol'] ?? null;
            $native = $currency['native'] ?? null;
            $symbolLeft = $currency['symbol_left'] ?? null;
            $spaceBetween = $currency['space_between'] ?? null;
            $exp = $currency['exp'];
            $minAmount = $currency['min_amount'];

            $rate = round(pow(10, $exp) / $minAmount, 4);

            $currencies[$code] = new Currency($code, $rate, $exp, $symbol, $native, $symbolLeft, $spaceBetween);
        }

        return count($currencies) === 0 ? null : array_values($currencies);
    }
}