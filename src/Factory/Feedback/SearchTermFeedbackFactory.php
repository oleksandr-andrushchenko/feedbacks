<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedback;

class SearchTermFeedbackFactory
{
    public function createSearchTermFeedback(SearchTerm $searchTerm, Feedback $feedback, ?array $extraSearchTerms = null): SearchTermFeedback
    {
        return new SearchTermFeedback(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedback->getId(),
            $feedback->getRating(),
            $feedback->getText(),
            $feedback->getTelegramChannelMessageIds(),
            $feedback->getUser()?->getId(),
            $feedback->getUser()?->hasActiveSubscription(),
            $feedback->getUser()?->getCountryCode(),
            $feedback->getUser()?->getLocaleCode(),
            $feedback->getMessengerUser()?->getId(),
            $feedback->getTelegramBot()?->getId(),
            empty($extraSearchTerms) ? null : $extraSearchTerms
        );
    }
}