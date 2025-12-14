<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Enum\Feedback\Rating;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Model\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreateFeedbackTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
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
     * @param CreateFeedbackTelegramBotConversationState $data
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        $searchTermCallback = fn (SearchTermTransfer $searchTerm): array => $this->searchTermTransferNormalizer->normalize($searchTerm, $format, $context);
        return array_merge($this->baseConversationStateNormalizer->normalize($data, $format, $context), [
            'search_terms' => $data->getSearchTerms()->hasItems() ? array_map($searchTermCallback, $data->getSearchTerms()->getItems()) : null,
            'rating' => $data->getRating()?->value,
            'description' => $data->getDescription(),
            'created_id' => $data->getCreatedId(),
        ]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CreateFeedbackTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var CreateFeedbackTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $searchTermCallback = fn (array $searchTerm): SearchTermTransfer => $this->searchTermTransferDenormalizer->denormalize($searchTerm, SearchTermTransfer::class, $format, $context);
        $object
            ->setSearchTerms(new SearchTermsTransfer(isset($data['search_terms']) ? array_map($searchTermCallback, $data['search_terms']) : null))
            ->setRating(isset($data['rating']) ? Rating::from($data['rating']) : null)
            ->setDescription($data['description'] ?? null)
            ->setCreatedId($data['created_id'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === CreateFeedbackTelegramBotConversationState::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CreateFeedbackTelegramBotConversationState::class => false,
        ];
    }
}