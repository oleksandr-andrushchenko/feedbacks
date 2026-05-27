<?php
declare(strict_types=1);

namespace App\Service\Intl;

interface LocaleTranslationsProviderInterface
{
    public function getLocaleTranslations(): ?array;
}