<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Enum\Messenger\Messenger;
use App\Transfer\Messenger\MessengerUserTransfer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessengerUserTransferNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        /** @var MessengerUserTransfer $data */
        return [
            'messenger' => $data->getMessenger()->value,
            'identifier' => $data->getId(),
            'username' => $data->getUsername(),
            'name' => $data->getName(),
            'locale' => $data->getLocaleCode(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MessengerUserTransfer && in_array($format, [null], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): MessengerUserTransfer
    {
        return new MessengerUserTransfer(
            Messenger::from($data['messenger']),
            $data['identifier'],
            $data['username'] ?? null,
            $data['name'] ?? null,
            $data['locale'] ?? null
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === MessengerUserTransfer::class && in_array($format, [null], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MessengerUserTransfer::class => false,
        ];
    }
}