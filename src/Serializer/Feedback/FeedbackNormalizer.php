<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\Feedback;
use App\Service\Feedback\FeedbackService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    )
    {
    }

    /**
     * @param Feedback $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $messengerUser = $this->feedbackService->getMessengerUser($data);

            $result = [];

            if (!empty($messengerUser->getUsername())) {
                $result['user'] = sprintf('@%s', $messengerUser->getUsername());
            }

            foreach ($this->feedbackService->getSearchTerms($data) as $searchTerm) {
                $result[$searchTerm->getType()->name] = $searchTerm->getText();
            }

            $result['rate'] = $data->getRating()->name;
            $result['description'] = $data->getText();
            $result['bot'] = sprintf('@%s', $this->feedbackService->getTelegramBot($data)?->getUsername());

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