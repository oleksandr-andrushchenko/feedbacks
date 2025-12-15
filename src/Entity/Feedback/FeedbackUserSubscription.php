<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('FEEDBACK_USER_SUBSCRIPTION', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'FEEDBACK_USER_SUBSCRIPTIONS_BY_MESSENGER_USER',
            new PartitionKey(null, ['messengerUserId'], 'feedback_user_subscription_messenger_user_id_pk')
        ),
        new GlobalIndex(
            'FEEDBACK_USER_SUBSCRIPTIONS_BY_USER',
            new PartitionKey(null, ['userId'], 'feedback_user_subscription_user_id_pk'),
        ),
    ]
)]
class FeedbackUserSubscription implements Stringable
{
    public function __construct(
        #[Attribute('feedback_user_subscription_id')]
        private readonly string $id,
        private readonly User $user,
        #[Attribute('feedback_subscription_plan_name')]
        private readonly FeedbackSubscriptionPlanName $subscriptionPlan,
        #[Attribute('subscription_expire_at')]
        private readonly DateTimeInterface $expireAt,
        private readonly ?MessengerUser $messengerUser = null,
        private readonly ?TelegramBotPayment $telegramPayment = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('updated_at')]
        private ?DateTimeInterface $updatedAt = null,
        #[Attribute('user_id')]
        private ?string $userId = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
        #[Attribute('telegram_payment_id')]
        private ?string $telegramPaymentId = null,
    )
    {
        $this->userId = $this->user->getId();
        $this->messengerUserId = $this->messengerUser?->getId();
        $this->telegramPaymentId = $this->telegramPayment?->getId();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSubscriptionPlan(): FeedbackSubscriptionPlanName
    {
        return $this->subscriptionPlan;
    }

    public function getTelegramPayment(): ?TelegramBotPayment
    {
        return $this->telegramPayment;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }

    public function getTelegramPaymentId(): ?string
    {
        return $this->telegramPaymentId;
    }
}
