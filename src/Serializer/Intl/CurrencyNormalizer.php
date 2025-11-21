<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Entity\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CurrencyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Currency $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'internal') {
            return [
                'c' => $data->getCode(),
                'r' => $data->getRate(),
                'e' => $data->getExp(),
//            's' => $object->getSymbol(),
                'n' => $data->getNative(),
                'sl' => $data->isSymbolLeft(),
                'sb' => $data->isSpaceBetween(),
            ];
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Currency && in_array($format, ['internal'], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Currency
    {
        if ($format === 'internal') {
            return new $type(
                $data['c'],
                $data['r'],
                $data['e'],
//            symbol: $data['s'] ?? null,
                native: $data['n'] ?? null,
                symbolLeft: $data['sl'] ?? null,
                spaceBetween: $data['sb'] ?? null,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === Currency::class && in_array($format, ['internal'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Currency::class => false,
        ];
    }
}