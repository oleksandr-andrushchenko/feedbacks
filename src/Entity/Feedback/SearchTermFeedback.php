<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('SEARCH_TERM', ['searchTermId']),
    new SortKey('FEEDBACK', ['feedbackId']),
    [
        new GlobalIndex(
            'SEARCH_TERM_FEEDBACKS_BY_SEARCH_TERM_NORMALIZED_TEXT_CREATED',
            new PartitionKey(null, ['searchTermNormalizedText'], 'search_term_feedback_normalized_text_pk'),
            new SortKey(null, ['createdAt'], 'search_term_feedback_created_at_sk'),
        ),
    ]
)]
class SearchTermFeedback
{
    /**
     * @param array<SearchTerm>|null $extraSearchTerms
     */
    public function __construct(
        #[Attribute('search_term_id')]
        private readonly string $searchTermId,
        #[Attribute('search_term_text')]
        private readonly string $searchTermText,
        #[Attribute('search_term_normalized_text')]
        private readonly string $searchTermNormalizedText,
        #[Attribute('search_term_type')]
        private readonly SearchTermType $searchTermType,
        #[Attribute('feedback_id')]
        private readonly string $feedbackId,
        #[Attribute('feedback_rating')]
        private readonly Rating $feedbackRating,
        #[Attribute('feedback_text')]
        private readonly ?string $feedbackText,
        #[Attribute('feedback_telegram_channel_message_ids')]
        private ?array $feedbackTelegramChannelMessageIds,
        #[Attribute('user_id')]
        private readonly string $userId,
        #[Attribute('user_active_subscription')]
        private readonly bool $userActiveSubscription,
        #[Attribute('user_country_code')]
        private readonly ?string $userCountryCode,
        #[Attribute('user_locale_code')]
        private readonly ?string $userLocaleCode,
        #[Attribute('messenger_user_id')]
        private readonly ?string $messengerUserId,
        #[Attribute('telegram_bot_id')]
        private readonly ?string $telegramBotId,
        /** @var array<SearchTerm> */
        #[Attribute('extra_search_terms')]
        private readonly ?array $extraSearchTerms = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getSearchTermId(): string
    {
        return $this->searchTermId;
    }

    public function getSearchTermText(): string
    {
        return $this->searchTermText;
    }

    public function getSearchTermNormalizedText(): string
    {
        return $this->searchTermNormalizedText;
    }

    public function getSearchTermType(): SearchTermType
    {
        return $this->searchTermType;
    }

    public function getSearchTerm(): SearchTerm
    {
        return new SearchTerm(
            $this->getSearchTermId(),
            $this->getSearchTermText(),
            $this->getSearchTermNormalizedText(),
            $this->getSearchTermType(),
        );
    }

    public function getFeedbackId(): string
    {
        return $this->feedbackId;
    }

    public function getFeedbackRating(): Rating
    {
        return $this->feedbackRating;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }

    public function getFeedbackText(): ?string
    {
        return $this->feedbackText;
    }

    public function hasUserActiveSubscription(): ?bool
    {
        return $this->userActiveSubscription;
    }

    public function getUserCountryCode(): ?string
    {
        return $this->userCountryCode;
    }

    public function getUserLocaleCode(): ?string
    {
        return $this->userLocaleCode;
    }

    public function addFeedbackTelegramChannelMessageId(int $messageId): self
    {
        if ($this->feedbackTelegramChannelMessageIds === null) {
            $this->feedbackTelegramChannelMessageIds = [];
        }

        $this->feedbackTelegramChannelMessageIds[] = $messageId;

        return $this;
    }

    public function getTelegramBotId(): ?string
    {
        return $this->telegramBotId;
    }

    public function getExtraSearchTerms(): ?array
    {
        if ($this->extraSearchTerms === null) {
            return null;
        }

        return array_map(
            static fn ($term) => $term instanceof SearchTerm ? $term : new SearchTerm(
                $term['id'],
                $term['text'],
                $term['normalizedText'],
                SearchTermType::from($term['type']),
            ),
            $this->extraSearchTerms
        );
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
