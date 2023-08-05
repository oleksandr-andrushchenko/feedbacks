<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSearchNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackSearch $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return [
                'messenger_username' => sprintf('@%s', $object->getMessengerUser()->getUsername()),
                'messenger' => $object->getMessengerUser()->getMessenger()->name,
                'search_term' => $object->getSearchTermText(),
                'search_term_type' => $object->getSearchTermType()->name,
                'created_at' => $object->getCreatedAt()->getTimestamp(),
                'updated_at' => $object->getUpdatedAt()?->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackSearch && in_array($format, ['activity'], true);
    }
}