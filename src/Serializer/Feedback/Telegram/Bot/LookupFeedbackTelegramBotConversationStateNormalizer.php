<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\LookupFeedbackTelegramBotConversationState;
use App\Model\Telegram\TelegramBotConversationState;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LookupFeedbackTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $searchConversationStateNormalizer,
        private readonly DenormalizerInterface $searchConversationStateDenormalizer,
    )
    {
    }

    /**
     * @param LookupFeedbackTelegramBotConversationState $data
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return $this->searchConversationStateNormalizer->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LookupFeedbackTelegramBotConversationState && in_array($format, [null], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        return $this->searchConversationStateDenormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === LookupFeedbackTelegramBotConversationState::class && in_array($format, [null], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            LookupFeedbackTelegramBotConversationState::class => false,
        ];
    }
}