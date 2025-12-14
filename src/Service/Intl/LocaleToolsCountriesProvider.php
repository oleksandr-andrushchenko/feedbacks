<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Model\Intl\Country;

class LocaleToolsCountriesProvider implements CountriesProviderInterface
{
    public function __construct(
        private readonly string $dataDir,
    )
    {
    }

    public function getCountries(): ?array
    {
        $content = file_get_contents(sprintf('%s/locale_tools_countries.json', $this->dataDir));
        $data = json_decode($content, true);

        unset($content);

        if (!is_array($data)) {
            return null;
        }

        $countries = [];

        foreach ($data['default'] as $country) {
            if (!isset(
                $country['cca2'],
                $country['governance'],
                $country['governance']['isSovereign'],
                $country['languages'],
                $country['languages']['official'],
                $country['currencies'],
                $country['idd'],
                $country['idd']['prefix'],
                $country['idd']['suffixes'],
                $country['locale'],
                $country['locale']['timezones'],
            )) {
                continue;
            }

            if (!$country['governance']['isSovereign']) {
                continue;
            }

            $code = strtolower($country['cca2']);
            $locales = array_filter(array_map(static fn (array $language): ?string => $language['bcp47'] ?? null, $country['languages']['official']));

            if (count($locales) === 0) {
                continue;
            }

            if ($code === 'pl') {
                $locales[] = 'uk';
            }

            if ($code === 'by') {
                $currencies = [
                    'BLR',
                ];
            } else {
                $currencies = array_filter(array_map(static fn (array $currency): ?string => $currency['iso4217'] ?? null, $country['currencies']));

                if (count($currencies) === 0) {
                    continue;
                }
            }

            $phones = array_filter(array_map(static fn (string $suffix): string => ltrim($country['idd']['prefix'], '+') . $suffix, $country['idd']['suffixes']));

            if (count($phones) === 0) {
                continue;
            }

            $timezones = array_map(static fn (array $timezone): string => $timezone['name'], $country['locale']['timezones']);

            $countries[$code] = new Country($code, $currencies[0], $locales, $phones[0], $timezones, false);
        }

        return count($countries) === 0 ? null : array_values($countries);
    }
}