<?php

declare(strict_types=1);

namespace App\Serializer\User;

use App\Entity\User\UserContactMessage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserContactMessageNormalizer implements NormalizerInterface
{
    /**
     * @param UserContactMessage $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return [
                'messenger_username' => sprintf('@%s', $data->getMessengerUser()->getUsername()),
                'messenger' => $data->getMessengerUser()->getMessenger()->name,
                'text' => $data->getText(),
                'bot' => sprintf('@%s', $data->getTelegramBot()->getUsername()),
                'created_at' => $data->getCreatedAt()->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof UserContactMessage && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            UserContactMessage::class => false,
        ];
    }
}