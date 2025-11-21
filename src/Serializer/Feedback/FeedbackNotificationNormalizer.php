<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\Feedback\FeedbackNotification;

class FeedbackNotificationNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackNotification $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $result = [];

            $result['type'] = $data->getType()->name;

            $user = $data->getMessengerUser();

            if (!empty($user->getUsername())) {
                $result['user'] = sprintf('@%s', $user->getUsername());
            }

            $searchTerm = $data->getFeedbackSearchTerm();

            if ($searchTerm !== null) {
                $result[$searchTerm->getType()->name] = $searchTerm->getText();
            }

            $result['bot'] = sprintf('@%s', $data->getTelegramBot()->getUsername());

            return $result;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FeedbackNotification && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackNotification::class => false,
        ];
    }
}