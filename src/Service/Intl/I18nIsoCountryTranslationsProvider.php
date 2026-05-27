<?php
declare(strict_types=1);

namespace App\Service\Intl;

class I18nIsoCountryTranslationsProvider implements CountryTranslationsProviderInterface
{
    public function __construct(
        private readonly string $dataDir,
    )
    {
    }

    public function getCountryTranslations(): ?array
    {
        $content = file_get_contents(sprintf('%s/i18n_iso_country_translations.json', $this->dataDir));
        $data = json_decode($content, true);

        unset($content);

        if (!is_array($data)) {
            return null;
        }

        $translations = [];

        foreach ($data as $item) {
            if (!isset(
                $item['locale'],
                $item['countries'],
            )) {
                continue;
            }

            $locale = $item['locale'];
            $countries = $item['countries'];

            $translations[$locale] = [];
            foreach ($countries as $country => $translation) {
                $translations[$locale][strtolower($country)] = is_array($translation) ? $translation[0] : $translation;
            }
        }

        return count($translations) === 0 ? null : $translations;
    }
}