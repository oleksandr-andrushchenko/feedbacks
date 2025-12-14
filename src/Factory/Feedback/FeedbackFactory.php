<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;

class FeedbackFactory
{
    public function createFeedback(
        string $id,
        User $user,
        MessengerUser $messengerUser,
        /** @var array<SearchTerm> $searchTerms */
        array $searchTerms,
        Rating $rating,
        ?string $text,
        ?TelegramBot $telegramBot,
    ): Feedback
    {
        return new Feedback(
            id: $id,
            user: $user,
            messengerUser: $messengerUser,
            searchTerms: $searchTerms,
            rating: $rating,
            text: $text,
            telegramBot: $telegramBot,
        );
    }
}