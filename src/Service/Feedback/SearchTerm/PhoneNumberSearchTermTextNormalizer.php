<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Service\Intl\CountryProvider;

class PhoneNumberSearchTermTextNormalizer
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function normalizePhoneNumberSearchTermText(string $text, array $countryCodes = []): ?string
    {
        $normalizedText = preg_replace('/[^0-9]/', '', $text);
        $phoneCodes = array_combine(
            $countryCodes,
            array_map(
                fn (string $countryCode): string => $this->countryProvider->getCountry($countryCode)->getPhoneCode(),
                $countryCodes
            )
        );

        if (str_starts_with($text, '+')) {
            return $normalizedText;
        }

        if (isset($phoneCodes['ua']) && str_starts_with($normalizedText, '0') && strlen($normalizedText) === 10) {
            return substr($phoneCodes['ua'], 0, 2) . $normalizedText;
        }

        if (isset($phoneCodes['ru']) && str_starts_with($normalizedText, '7') && strlen($normalizedText) === 11) {
            return $normalizedText;
        }

        foreach ($phoneCodes as $phoneCode) {
            if (str_starts_with($normalizedText, $phoneCode)) {
                return $normalizedText;
            }
        }

        return $normalizedText;
    }
}