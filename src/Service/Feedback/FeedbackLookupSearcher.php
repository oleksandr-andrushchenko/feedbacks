<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackLookup;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Feedback\SearchTermFeedbackLookupDynamodbRepository;

class FeedbackLookupSearcher
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly SearchTermFeedbackLookupDynamodbRepository $searchTermFeedbackLookupDynamodbRepository,
        private readonly FeedbackLookupService $feedbackLookupService,
    )
    {
    }

    /**
     * @param SearchTerm $searchTerm
     * @param int $maxResults
     * @return array<FeedbackLookup>
     */
    public function searchFeedbackLookups(SearchTerm $searchTerm, int $maxResults = 20): array
    {
        $normalizedText = $searchTerm->getNormalizedText();

        if ($this->feedbackLookupRepository->getConfig()->isDynamodb()) {
            $searchTermFeedbackLookups = $this->searchTermFeedbackLookupDynamodbRepository->findBySearchTermNormalizedText($normalizedText);
            $feedbackLookups = array_map(
                static fn (SearchTermFeedbackLookup $searchTermFeedbackLookup) => new FeedbackLookup(
                    id: $searchTermFeedbackLookup->getFeedbackLookupId(),
                    // todo: add extra search terms
                    searchTerm: $searchTermFeedbackLookup->getSearchTerm(),
                    userId: $searchTermFeedbackLookup->getUserId(),
                    hasActiveSubscription: $searchTermFeedbackLookup->hasUserActiveSubscription(),
                    countryCode: $searchTermFeedbackLookup->getUserCountryCode(),
                    localeCode: $searchTermFeedbackLookup->getUserLocaleCode(),
                    messengerUserId: $searchTermFeedbackLookup->getMessengerUserId(),
                    telegramBotId: $searchTermFeedbackLookup->getTelegramBotId(),
                ),
                $searchTermFeedbackLookups
            );
        } else {
            $feedbackLookups = $this->feedbackLookupRepository->findByNormalizedText($normalizedText, $maxResults);
        }

        $feedbackLookups = array_filter(
            $feedbackLookups,
            function (FeedbackLookup $feedbackLookup) use ($searchTerm): bool {
                $searchTerm_ = $this->feedbackLookupService->getSearchTerm($feedbackLookup);

                return $searchTerm->getType() === SearchTermType::unknown
                    || $searchTerm_->getType() === SearchTermType::unknown
                    || $searchTerm->getType() === $searchTerm_->getType();
            }
        );

        $feedbackLookups = array_values($feedbackLookups);
        $feedbackLookups = array_reverse($feedbackLookups, true);

        return array_slice($feedbackLookups, 0, $maxResults, true);
    }
}
