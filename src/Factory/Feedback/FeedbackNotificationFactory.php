<?php

declare(strict_types=1);

namespace App\Factory\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Service\IdGenerator;

class FeedbackNotificationFactory
{
    public function __construct(
        private IdGenerator $idGenerator,
    )
    {
    }

    public function createFeedbackNotification(
        FeedbackNotificationType $type,
        MessengerUser $messengerUser,
        ?SearchTerm $searchTerm = null,
        ?Feedback $feedback = null,
        ?Feedback $targetFeedback = null,
        ?FeedbackSearch $feedbackSearch = null,
        ?FeedbackSearch $targetFeedbackSearch = null,
        ?FeedbackLookup $feedbackLookup = null,
        ?FeedbackLookup $targetFeedbackLookup = null,
        ?FeedbackUserSubscription $feedbackUserSubscription = null,
        ?TelegramBot $telegramBot = null,
    ): FeedbackNotification
    {
        return new FeedbackNotification(
            id: $this->idGenerator->generateId(),
            type: $type,
            messengerUser: $messengerUser,
            searchTerm: $searchTerm,
            feedback: $feedback,
            targetFeedback: $targetFeedback,
            feedbackSearch: $feedbackSearch,
            targetFeedbackSearch: $targetFeedbackSearch,
            feedbackLookup: $feedbackLookup,
            targetFeedbackLookup: $targetFeedbackLookup,
            feedbackUserSubscription: $feedbackUserSubscription,
            telegramBot: $telegramBot,
        );
    }
}