<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Entity\Messenger\MessengerUser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessengerUserNormalizer implements NormalizerInterface
{
    /**
     * @param MessengerUser $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return array_filter([
                'messenger' => $data->getMessenger()->name,
                'username' => $data->getUsername() === null ? null : sprintf('@%s', $data->getUsername()),
                'name' => $data->getName() ?? null,
                'bot_ids' => $data->getBotIds() === null ? null : implode(', ', $data->getBotIds()),
                'created_at' => $data->getCreatedAt()?->format('d.m.Y H:i'),
            ]);
        }

        return array_filter([
            'messenger' => $data->getMessenger()->value,
            'identifier' => $data->getIdentifier(),
            'username' => $data->getUsername() ?? null,
            'name' => $data->getName() ?? null,
            'bot_ids' => $data->getBotIds() === null ? null : implode(', ', $data->getBotIds()),
            'username_history' => $data->getUsernameHistory() === null ? null : implode(', ', $data->getUsernameHistory()),
        ]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MessengerUser;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MessengerUser::class => false,
        ];
    }
}