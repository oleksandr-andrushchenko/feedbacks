<?php
declare(strict_types=1);

namespace App\Tests\Traits\Intl;

use App\Service\Intl\CountryProvider;

trait CountryProviderTrait
{
    public function getCountryProvider(): CountryProvider
    {
        return static::getContainer()->get('app.intl_country_provider');
    }
}