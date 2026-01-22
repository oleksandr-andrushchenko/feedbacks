<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Model\Feedback\Telegram\FeedbackSubscriptionPlan;
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
        return $data instanceof FeedbackSubscriptionPlan && in_array($format, [null], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): FeedbackSubscriptionPlan
    {
        return new FeedbackSubscriptionPlan(
            FeedbackSubscriptionPlanName::from($data['name']),
            $data['datetime_modifier'],
            $data['default_price'],
            $data['prices'] ?? null,
            $data['countries'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === FeedbackSubscriptionPlan::class && in_array($format, [null], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackSubscriptionPlan::class => false,
        ];
    }
}