<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Service\IdGenerator;

class FeedbackSearchFactory
{
    public function __construct(
        private IdGenerator $idGenerator,
    )
    {
    }

    public function createFeedbackSearch(
        SearchTerm $searchTerm,
        User $user,
        MessengerUser $messengerUser,
        ?TelegramBot $telegramBot,
    ): FeedbackSearch
    {
        return new FeedbackSearch(
            id: $this->idGenerator->generateId(),
            searchTerm: $searchTerm,
            user: $user,
            messengerUser: $messengerUser,
            telegramBot: $telegramBot,
        );
    }
}