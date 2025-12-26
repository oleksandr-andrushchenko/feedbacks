<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackSearch;
use App\Service\Feedback\FeedbackSearchService;

class SearchTermFeedbackSearchFactory
{
    public function __construct(
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
    }

    public function createSearchTermFeedbackSearch(SearchTerm $searchTerm, FeedbackSearch $feedbackSearch, ?array $extraSearchTerms = null): SearchTermFeedbackSearch
    {
        $user = $this->feedbackSearchService->getUser($feedbackSearch);
        $messengerUser = $this->feedbackSearchService->getMessengerUser($feedbackSearch);
        $telegramBot = $this->feedbackSearchService->getTelegramBot($feedbackSearch);

        return new SearchTermFeedbackSearch(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedbackSearch->getId(),
            $user?->getId(),
            $user?->hasActiveSubscription(),
            $user?->getCountryCode(),
            $user?->getLocaleCode(),
            $messengerUser?->getId(),
            $telegramBot?->getId(),
            empty($extraSearchTerms) ? null : $extraSearchTerms
        );
    }
}