<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\Feedback;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackNormalizer implements NormalizerInterface
{
    /**
     * @param Feedback $data
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

            foreach ($data->getSearchTerms() as $searchTerm) {
                $result[$searchTerm->getType()->name] = $searchTerm->getText();
            }

            $result['rate'] = $data->getRating()->name;
            $result['description'] = $data->getDescription();
            $result['bot'] = sprintf('@%s', $data->getTelegramBot()->getUsername());

            return $result;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Feedback && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Feedback::class => false,
        ];
    }
}