<?php
declare(strict_types=1);

namespace App\Service\Intl;

use App\Service\Util\String\MbUcFirster;

class I18NIsoLocaleTranslationsProvider implements LocaleTranslationsProviderInterface
{
    public function __construct(
        private readonly string $dataDir,
        private readonly MbUcFirster $mbUcFirster,
    )
    {
    }

    public function getLocaleTranslations(): ?array
    {
        $content = file_get_contents(sprintf('%s/i18n_iso_language_translations.json', $this->dataDir));
        $data = json_decode($content, true);

        unset($content);

        if (!is_array($data)) {
            return null;
        }

        $translations = [];

        foreach ($data as $item) {
            if (!isset(
                $item['locale'],
                $item['languages'],
            )) {
                continue;
            }

            $locale = $item['locale'];
            $languages = $item['languages'];

            $translations[$locale] = [];
            foreach ($languages as $language => $translation) {
                $translations[$locale][$language] = $this->mbUcFirster->mbUcFirst(is_array($translation) ? $translation[0] : $translation);
            }
        }

        return count($translations) === 0 ? null : $translations;
    }
}