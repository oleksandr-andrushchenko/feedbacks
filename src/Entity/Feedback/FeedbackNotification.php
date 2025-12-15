<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('FEEDBACK_NOTIFICATION', ['id']),
    new SortKey('META'),
)]
class FeedbackNotification implements Stringable
{
    public function __construct(
        #[Attribute('feedback_notification_id')]
        private readonly string $id,
        #[Attribute]
        private readonly FeedbackNotificationType $type,
        private readonly MessengerUser $messengerUser,
        private readonly ?SearchTerm $searchTerm = null,
        private readonly ?Feedback $feedback = null,
        private readonly ?Feedback $targetFeedback = null,
        private readonly ?FeedbackSearch $feedbackSearch = null,
        private readonly ?FeedbackSearch $targetFeedbackSearch = null,
        private readonly ?FeedbackLookup $feedbackLookup = null,
        private readonly ?FeedbackLookup $targetFeedbackLookup = null,
        private readonly ?FeedbackUserSubscription $feedbackUserSubscription = null,
        private readonly ?TelegramBot $telegramBot = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
        #[Attribute('search_term_id')]
        private ?string $searchTermId = null,
        #[Attribute('feedback_id')]
        private ?string $feedbackId = null,
        #[Attribute('target_feedback_id')]
        private ?string $targetFeedbackId = null,
        #[Attribute('feedback_search_id')]
        private ?string $feedbackSearchId = null,
        #[Attribute('target_feedback_search_id')]
        private ?string $targetFeedbackSearchId = null,
        #[Attribute('feedback_lookup_id')]
        private ?string $feedbackLookupId = null,
        #[Attribute('target_feedback_lookup_id')]
        private ?string $targetFeedbackLookupId = null,
        #[Attribute('feedback_user_subscription_id')]
        private ?string $feedbackUserSubscriptionId = null,
        #[Attribute('telegram_bot_id')]
        private ?string $telegramBotId = null,
    )
    {
        $this->createdAt ??= new DateTimeImmutable();
        $this->messengerUserId = $this->messengerUser->getId();
        $this->searchTermId = $this->searchTerm?->getId();
        $this->feedbackId = $this->feedback?->getId();
        $this->targetFeedbackId = $this->targetFeedback?->getId();
        $this->feedbackSearchId = $this->feedbackSearch?->getId();
        $this->targetFeedbackSearchId = $this->targetFeedbackSearch?->getId();
        $this->feedbackLookupId = $this->feedbackLookup?->getId();
        $this->targetFeedbackLookupId = $this->targetFeedbackLookup?->getId();
        $this->feedbackUserSubscriptionId = $this->feedbackUserSubscription?->getId();
        $this->telegramBotId = $this->telegramBot?->getId();
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
        return $this->searchTerm;
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
