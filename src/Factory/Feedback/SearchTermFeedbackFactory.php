<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedback;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;

class SearchTermFeedbackFactory
{
    public function createSearchTermFeedback(
        SearchTerm $searchTerm,
        Feedback $feedback,
        User $user,
        ?MessengerUser $messengerUser = null,
        ?TelegramBot $telegramBot = null,
        ?array $extraSearchTerms = null,
    ): SearchTermFeedback
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