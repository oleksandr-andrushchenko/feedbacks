<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackUserSubscriptionNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackUserSubscription $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $user = $data->getMessengerUser();

            $result = [];

            if (!empty($user?->getUsername())) {
                $result['user'] = sprintf('@%s', $user->getUsername());
            }

            $result['plan'] = $data->getSubscriptionPlan()->name;

            $telegramBot = $data->getTelegramPayment()?->getBot();

            if (!empty($telegramBot)) {
                $result['telegram_bot'] = sprintf('@%s', $telegramBot->getUsername());
            }

            return $result;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FeedbackUserSubscription && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackUserSubscription::class => false,
        ];
    }
}