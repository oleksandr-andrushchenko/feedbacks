<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSearchNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackSearch $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $user = $data->getMessengerUser();

            $result = [];

            if (!empty($user->getUsername())) {
                $result['user'] = sprintf('@%s', $user->getUsername());
            }

            $result[$data->getSearchTerm()->getType()->name] = $data->getSearchTerm()->getText();

            $result['bot'] = sprintf('@%s', $data->getTelegramBot()->getUsername());

            return $result;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FeedbackSearch && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackSearch::class => false,
        ];
    }
}