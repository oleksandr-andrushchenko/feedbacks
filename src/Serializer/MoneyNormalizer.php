<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Model\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    /**
     * @param Money $data
     * @param string|null $format
     * @param array $context
     * @return string[]
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return [
            'amount' => $data->getAmount(),
            'currency' => $data->getCurrency(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Money && in_array($format, [null], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Money::class => false,
        ];
    }
}