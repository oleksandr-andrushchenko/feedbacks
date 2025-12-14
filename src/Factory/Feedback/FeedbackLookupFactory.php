<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;

class FeedbackLookupFactory
{
    public function createFeedbackLookup(
        string $id,
        SearchTerm $searchTerm,
        User $user,
        MessengerUser $messengerUser,
        ?TelegramBot $telegramBot,
    ): FeedbackLookup
    {
        return new FeedbackLookup(
            id: $id,
            searchTerm: $searchTerm,
            user: $user,
            messengerUser: $messengerUser,
            telegramBot: $telegramBot,
        );
    }
}