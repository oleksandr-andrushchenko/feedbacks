<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackSearch;

class SearchTermFeedbackSearchFactory
{
    public function createSearchTermFeedbackSearch(SearchTerm $searchTerm, FeedbackSearch $feedbackSearch, ?array $extraSearchTerms = null): SearchTermFeedbackSearch
    {
        return new SearchTermFeedbackSearch(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedbackSearch->getId(),
            $feedbackSearch->getUser()?->getId(),
            $feedbackSearch->getUser()?->hasActiveSubscription(),
            $feedbackSearch->getUser()?->getCountryCode(),
            $feedbackSearch->getUser()?->getLocaleCode(),
            $feedbackSearch->getMessengerUser()?->getId(),
            $feedbackSearch->getTelegramBot()?->getId(),
            empty($extraSearchTerms) ? null : $extraSearchTerms
        );
    }
}