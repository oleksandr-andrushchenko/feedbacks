<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackLookup;

class SearchTermFeedbackLookupFactory
{
    public function createSearchTermFeedbackLookup(SearchTerm $searchTerm, FeedbackLookup $feedbackLookup, ?array $extraSearchTerms = null): SearchTermFeedbackLookup
    {
        return new SearchTermFeedbackLookup(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedbackLookup->getId(),
            $feedbackLookup->getUser()?->getId(),
            $feedbackLookup->getUser()?->hasActiveSubscription(),
            $feedbackLookup->getUser()?->getCountryCode(),
            $feedbackLookup->getUser()?->getLocaleCode(),
            $feedbackLookup->getMessengerUser()?->getId(),
            $feedbackLookup->getTelegramBot()?->getId(),
            empty($extraSearchTerms) ? null : $extraSearchTerms
        );
    }
}