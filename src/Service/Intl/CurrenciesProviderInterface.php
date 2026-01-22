<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Model\Intl\Currency;

interface CurrenciesProviderInterface
{
    /**
     * @return Currency[]|null
     */
    public function getCurrencies(): ?array;
}