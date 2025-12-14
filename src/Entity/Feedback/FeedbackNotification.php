<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use DateTimeInterface;
use Stringable;

class FeedbackNotification implements Stringable
{
    public function __construct(
        private readonly string $id,
        private readonly FeedbackNotificationType $type,
        private readonly MessengerUser $messengerUser,
        private readonly ?SearchTerm $feedbackSearchTerm = null,
        private readonly ?Feedback $feedback = null,
        private readonly ?Feedback $targetFeedback = null,
        private readonly ?FeedbackSearch $feedbackSearch = null,
        private readonly ?FeedbackSearch $targetFeedbackSearch = null,
        private readonly ?FeedbackLookup $feedbackLookup = null,
        private readonly ?FeedbackLookup $targetFeedbackLookup = null,
        private readonly ?FeedbackUserSubscription $feedbackUserSubscription = null,
        private readonly ?TelegramBot $telegramBot = null,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): FeedbackNotificationType
    {
        return $this->type;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getFeedbackSearchTerm(): ?SearchTerm
    {
        return $this->feedbackSearchTerm;
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function getTargetFeedback(): ?Feedback
    {
        return $this->targetFeedback;
    }

    public function getFeedbackSearch(): ?FeedbackSearch
    {
        return $this->feedbackSearch;
    }

    public function getTargetFeedbackSearch(): ?FeedbackSearch
    {
        return $this->targetFeedbackSearch;
    }

    public function getFeedbackLookup(): ?FeedbackLookup
    {
        return $this->feedbackLookup;
    }

    public function getTargetFeedbackLookup(): ?FeedbackLookup
    {
        return $this->targetFeedbackLookup;
    }

    public function getFeedbackUserSubscription(): ?FeedbackUserSubscription
    {
        return $this->feedbackUserSubscription;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
