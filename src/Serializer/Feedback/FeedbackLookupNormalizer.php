<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Service\Feedback\FeedbackLookupService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackLookupNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly FeedbackLookupService $feedbackLookupService,
    )
    {
    }

    /**
     * @param FeedbackLookup $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $searchTerm = $this->feedbackLookupService->getSearchTerm($data);
            $telegramBot = $this->feedbackLookupService->getTelegramBot($data);
            $messengerUser = $this->feedbackLookupService->getMessengerUser($data);

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
        return $data instanceof FeedbackLookup && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedbackLookup::class => false,
        ];
    }
}