<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackLookup;
use App\Service\Feedback\FeedbackLookupService;

class SearchTermFeedbackLookupFactory
{
    public function __construct(
        private readonly FeedbackLookupService $feedbackLookupService,
    )
    {
    }

    public function createSearchTermFeedbackLookup(SearchTerm $searchTerm, FeedbackLookup $feedbackLookup, ?array $extraSearchTerms = null): SearchTermFeedbackLookup
    {
        $user = $this->feedbackLookupService->getUser($feedbackLookup);
        $messengerUser = $this->feedbackLookupService->getMessengerUser($feedbackLookup);
        $telegramBot = $this->feedbackLookupService->getTelegramBot($feedbackLookup);

        return new SearchTermFeedbackLookup(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedbackLookup->getId(),
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