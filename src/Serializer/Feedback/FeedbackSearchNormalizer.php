<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Service\Feedback\FeedbackSearchService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSearchNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
    }

    /**
     * @param FeedbackSearch $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $messengerUser = $this->feedbackSearchService->getMessengerUser($data);
            $searchTerm = $this->feedbackSearchService->getSearchTerm($data);
            $telegramBot = $this->feedbackSearchService->getTelegramBot($data);

            $result = [];

            if (!empty($messengerUser->getUsername())) {
                $result['user'] = sprintf('@%s', $messengerUser->getUsername());
            }

            $result[$searchTerm->getType()->name] = $searchTerm->getText();
            $result['bot'] = sprintf('@%s', $telegramBot->getUsername());

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