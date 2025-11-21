<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversationState;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param TelegramBotConversationState $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return [
            'step' => $data->getStep(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var TelegramBotConversationState $object */
        $object = new $type();

        $object
            ->setStep($data['step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && ($type === TelegramBotConversationState::class || get_parent_class($type) === TelegramBotConversationState::class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TelegramBotConversationState::class => false,
        ];
    }
}