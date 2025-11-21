<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSubscriptionPlanNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param FeedbackSubscriptionPlan $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return [
            'name' => $data->getName()->value,
            'datetime_modifier' => $data->getDatetimeModifier(),
            'default_price' => $data->getDefaultPrice(),
            'prices' => $data->getPrices() === null ? null : $data->getPrices(),
            'countries' => $data->getCountries() === null ? null : $data->getCountries(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FeedbackSubscriptionPlan;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): FeedbackSubscriptionPlan
    {
        return new $type(
            FeedbackSubscriptionPlanName::from($data['name']),
            $data['datetime_modifier'],
            $data['default_price'],
            $data['prices'] ?? null,
            $data['countries'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === FeedbackSubscriptionPlan::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackSubscriptionPlan::class => false,
        ];
    }
}