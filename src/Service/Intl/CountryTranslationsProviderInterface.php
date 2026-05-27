<?php
declare(strict_types=1);

namespace App\Service\Intl;

interface CountryTranslationsProviderInterface
{
    public function getCountryTranslations(): ?array;
}