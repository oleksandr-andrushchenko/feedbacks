<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackSearch;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;

class SearchTermFeedbackSearchFactory
{
    public function createSearchTermFeedbackSearch(
        SearchTerm $searchTerm,
        FeedbackSearch $feedbackSearch,
        User $user,
        ?MessengerUser $messengerUser = null,
        ?TelegramBot $telegramBot = null,
        ?array $extraSearchTerms = null,
    ): SearchTermFeedbackSearch
    {
        return new SearchTermFeedbackSearch(
            $searchTerm->getId(),
            $searchTerm->getText(),
            $searchTerm->getNormalizedText(),
            $searchTerm->getType(),
            $feedbackSearch->getId(),
            $user->getId(),
            $user->hasActiveSubscription(),
            $user->getCountryCode(),
            $user->getLocaleCode(),
            $messengerUser?->getId(),
            $telegramBot?->getId(),
            empty($extraSearchTerms) ? null : $extraSearchTerms
        );
    }
}