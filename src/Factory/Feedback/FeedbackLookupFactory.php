<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Service\IdGenerator;

class FeedbackLookupFactory
{
    public function __construct(
        private IdGenerator $idGenerator,
    )
    {
    }

    public function createFeedbackLookup(
        SearchTerm $searchTerm,
        User $user,
        MessengerUser $messengerUser,
        ?TelegramBot $telegramBot,
    ): FeedbackLookup
    {
        return new FeedbackLookup(
            id: $this->idGenerator->generateId(),
            searchTerm: $searchTerm,
            user: $user,
            messengerUser: $messengerUser,
            telegramBot: $telegramBot,
        );
    }
}