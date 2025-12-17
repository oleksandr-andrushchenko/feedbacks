<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Service\IdGenerator;

class FeedbackFactory
{
    public function __construct(
        private IdGenerator $idGenerator,
    )
    {
    }

    public function createFeedback(
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
            id: $this->idGenerator->generateId(),
            user: $user,
            messengerUser: $messengerUser,
            searchTerms: $searchTerms,
            rating: $rating,
            text: $text,
            telegramBot: $telegramBot,
        );
    }
}