<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Entity\Intl\Level1Region;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Level1RegionNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Level1Region $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'internal') {
            return [
                'id' => $data->getId(),
                'cc' => $data->getCountryCode(),
                'n' => $data->getName(),
                'tz' => $data->getTimezone(),
            ];
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Level1Region && in_array($format, ['internal'], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Level1Region
    {
        if ($format === 'internal') {
            return new Level1Region(
                $data['id'],
                $data['cc'],
                $data['n'],
                $data['tz'],
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === Level1Region::class && in_array($format, ['internal'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Level1Region::class => false,
        ];
    }
}