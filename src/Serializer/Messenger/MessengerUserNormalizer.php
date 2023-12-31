<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Entity\Messenger\MessengerUser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessengerUserNormalizer implements NormalizerInterface
{
    /**
     * @param MessengerUser $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return array_filter([
                'messenger' => $object->getMessenger()->name,
                'username' => $object->getUsername() === null ? null : sprintf('@%s', $object->getUsername()),
                'name' => $object->getName() ?? null,
                'bot_ids' => $object->getBotIds() === null ? null : implode(', ', $object->getBotIds()),
                'created_at' => $object->getCreatedAt()?->format('d.m.Y H:i'),
            ]);
        }

        return array_filter([
            'messenger' => $object->getMessenger()->value,
            'identifier' => $object->getIdentifier(),
            'username' => $object->getUsername() ?? null,
            'name' => $object->getName() ?? null,
            'bot_ids' => $object->getBotIds() === null ? null : implode(', ', $object->getBotIds()),
            'username_history' => $object->getUsernameHistory() === null ? null : implode(', ', $object->getUsernameHistory()),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof MessengerUser;
    }
}