<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Model\Intl\Locale;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly array $supportedLocales,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function hasLocale(string $code): bool
    {
        return array_key_exists($code, $this->supportedLocales);
    }

    public function getLocale(string $code): ?Locale
    {
        if (!$this->hasLocale($code)) {
            return null;
        }

        return $this->denormalize($this->supportedLocales[$code]);
    }

    public function getLocaleIcon(Locale $locale): string
    {
        return $this->countryProvider->getCountryIconByCode($locale->getFlag());
    }

    public function getUnknownLocaleIcon(): string
    {
        return $this->countryProvider->getUnknownCountryIcon();
    }

    public function getLocaleName(Locale $localeObj, string $localeCode = null): string
    {
        return $this->translator->trans($localeObj->getCode(), domain: 'locale', locale: $localeCode);
    }

    public function getUnknownLocaleName(string $locale = null): string
    {
        return $this->translator->trans('zz', domain: 'locale', locale: $locale);
    }

    public function getLocaleComposeName(Locale $localeObj = null, string $localeCode = null): string
    {
        if ($localeObj === null) {
            return join(' ', [
                $this->getUnknownLocaleIcon(),
                $this->getUnknownLocaleName($localeCode),
            ]);
        }

        return join(' ', [
            $this->getLocaleIcon($localeObj),
            $this->getLocaleName($localeObj, $localeCode),
        ]);
    }

    /**
     * @param string|null $countryCode
     * @return Locale[]
     */
    public function getLocales(string $countryCode = null): array
    {
        $data = $this->supportedLocales;

        if ($countryCode !== null) {
            $filter = $this->countryProvider->getCountry($countryCode)->getLocaleCodes() ?? [];
            $data = array_filter($data, static fn ($code): bool => in_array($code, $filter, true), ARRAY_FILTER_USE_KEY);
        }

        return array_map(fn ($record): Locale => $this->denormalize($record), array_values($data));
    }

    private function denormalize(array $record): Locale
    {
        return new Locale(
            $code = $record['code'],
            $record['flag'] ?? $code
        );
    }
}