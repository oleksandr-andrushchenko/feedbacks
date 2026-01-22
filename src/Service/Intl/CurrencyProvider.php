<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Model\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrencyProvider
{
    public function __construct(
        private readonly string $dataPath,
        private DenormalizerInterface $denormalizer,
        private readonly TranslatorInterface $translator,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function hasCurrency(string $code): bool
    {
        return array_key_exists($code, $this->getData());
    }

    public function getCurrency(string $code): ?Currency
    {
        if (!$this->hasCurrency($code)) {
            return null;
        }

        return $this->denormalize($this->getData()[$code]);
    }

    public function getCurrencyIcon(Currency $currency): string
    {
        $code = match ($currency->getCode()) {
            'EUR' => 'eu',
            'USD' => 'us',
            default => null,
        };
        if ($code === null) {
            $country = $this->countryProvider->getCountryByCurrency($currency->getCode());
            $code = $country->getCode();
        }

        return "\xF0\x9F\x87" . chr(ord($code[0]) + 0x45) . "\xF0\x9F\x87" . chr(ord($code[1]) + 0x45);
    }

    public function getUnknownCurrencyIcon(): string
    {
        return $this->countryProvider->getUnknownCountryIcon();
    }

    public function getCurrencyName(Currency $currency, string $localeCode = null): string
    {
        return $this->translator->trans($currency->getCode(), domain: 'currency', locale: $localeCode);
    }

    public function getUnknownCurrencyName(string $localeCode = null): string
    {
        return $this->translator->trans('ZZZ', domain: 'currency', locale: $localeCode);
    }

    public function getCurrencyComposeName(Currency $currency = null, string $localeCode = null): string
    {
        if ($currency === null) {
            return join(' ', [
                $this->getUnknownCurrencyIcon(),
                $this->getUnknownCurrencyName($localeCode),
            ]);
        }

        return join(' ', [
            $this->getCurrencyIcon($currency),
            $this->getCurrencyName($currency, $localeCode),
        ]);
    }

    /**
     * @param array|null $currencyCodes
     * @return Currency[]
     */
    public function getCurrencies(array $currencyCodes = null): array
    {
        $data = $this->getData();

        if ($currencyCodes !== null) {
            $data = array_filter($data, static fn ($code): bool => in_array($code, $currencyCodes, true), ARRAY_FILTER_USE_KEY);
        }

        return array_map(fn ($record): Currency => $this->denormalize($record), $currencyCodes === null ? $data : array_values($data));
    }

    private function denormalize(array $record): Currency
    {
        return $this->denormalizer->denormalize($record, Currency::class, format: 'internal');
    }

    private function getData(): array
    {
        static $data = null;

        if ($data === null) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);
        }

        return $data;
    }
}