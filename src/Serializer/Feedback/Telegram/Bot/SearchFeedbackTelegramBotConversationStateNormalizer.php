<?php
declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Model\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermsTransfer;
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
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        $searchTermCallback = fn (SearchTermTransfer $searchTerm): array => $this->searchTermTransferNormalizer
            ->normalize($searchTerm, $format, $context)
        ;
        return array_merge($this->baseConversationStateNormalizer->normalize($data, $format, $context), [
            'details' => $data->getDetails(),
            'search_terms' => $data->getSearchTerms()?->hasItems()
                ? array_map($searchTermCallback, $data->getSearchTerms()->getItems())
                : null,
        ]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SearchFeedbackTelegramBotConversationState && in_array($format, [null], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var SearchFeedbackTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $searchTermCallback = fn (array $searchTerm): SearchTermTransfer => $this->searchTermTransferDenormalizer
            ->denormalize($searchTerm, SearchTermTransfer::class, $format, $context)
        ;
        $object
            ->setDetails($data['details'] ?? null)
            ->setSearchTerms(
                isset($data['search_terms'])
                    ? new SearchTermsTransfer(array_map($searchTermCallback, $data['search_terms']))
                    : null
            )
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data)
            && $type === SearchFeedbackTelegramBotConversationState::class
            && $format === null;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SearchFeedbackTelegramBotConversationState::class => false,
        ];
    }
}
