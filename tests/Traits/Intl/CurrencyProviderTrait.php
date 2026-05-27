<?php
declare(strict_types=1);

namespace App\Tests\Traits\Intl;

use App\Service\Intl\CurrencyProvider;

trait CurrencyProviderTrait
{
    public function getCurrencyProvider(): CurrencyProvider
    {
        return static::getContainer()->get('app.intl_currency_provider');
    }
}