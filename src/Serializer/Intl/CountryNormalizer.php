<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Model\Intl\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CountryNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const LEVEL_1_REGIONS_DUMPED_KEY = 'l1rd';

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'internal') {
            return array_filter([
                'c' => $data->getCode(),
                'cu' => $data->getCurrencyCode(),
                'l' => $data->getLocaleCodes(),
                'p' => $data->getPhoneCode(),
                't' => $data->getTimezones(),
                self::LEVEL_1_REGIONS_DUMPED_KEY => $data->level1RegionsDumped() ? true : null,
            ]);
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Country && in_array($format, ['internal'], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Country
    {
        if ($format === 'internal') {
            return new $type(
                $data['c'],
                $data['cu'],
                $data['l'],
                $data['p'],
                $data['t'],
                $data[self::LEVEL_1_REGIONS_DUMPED_KEY] ?? false,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === Country::class && in_array($format, ['internal'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Country::class => false,
        ];
    }
}