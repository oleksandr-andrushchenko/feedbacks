<?php

declare(strict_types=1);

namespace App\Serializer\User;

use App\Entity\User\UserContactMessage;
use App\Service\User\UserContactMessageService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserContactMessageNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly UserContactMessageService $userContactMessageService,
    )
    {
    }

    /**
     * @param UserContactMessage $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $messengerUser = $this->userContactMessageService->getMessengerUser($data);
            $telegramBot = $this->userContactMessageService->getTelegramBot($data);

            return [
                'messenger_username' => sprintf('@%s', $messengerUser->getUsername()),
                'messenger' => $messengerUser->getMessenger()->name,
                'text' => $data->getText(),
                'bot' => sprintf('@%s', $telegramBot->getUsername()),
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