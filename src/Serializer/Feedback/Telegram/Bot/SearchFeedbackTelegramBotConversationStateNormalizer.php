<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Entity\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchFeedbackTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $baseConversationStateNormalizer,
        private readonly DenormalizerInterface $baseConversationStateDenormalizer,
        private readonly NormalizerInterface $searchTermTransferNormalizer,
        private readonly DenormalizerInterface $searchTermTransferDenormalizer,
    )
    {
    }

    /**
     * @param SearchFeedbackTelegramBotConversationState $data
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($data, $format, $context), [
            'search_term' => $data->getSearchTerm() === null ? null : $this->searchTermTransferNormalizer->normalize($data->getSearchTerm(), $format, $context),
        ]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SearchFeedbackTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var SearchFeedbackTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setSearchTerm(isset($data['search_term']) ? $this->searchTermTransferDenormalizer->denormalize($data['search_term'], SearchTermTransfer::class, $format, $context) : null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === SearchFeedbackTelegramBotConversationState::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SearchFeedbackTelegramBotConversationState::class => false,
        ];
    }
}