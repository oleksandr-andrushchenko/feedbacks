<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use DateTimeImmutable;
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
    #[Attribute('user_id')]
    private ?string $userId = null;
    #[Attribute('messenger_user_id')]
    private ?string $messengerUserId = null;
    #[Attribute('telegram_payment_id')]
    private ?string $telegramPaymentId = null;
    #[Attribute('created_at')]
    private ?DateTimeInterface $createdAt = null;
    #[Attribute('updated_at')]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(
        #[Attribute('feedback_user_subscription_id')]
        private readonly string $id,
        private readonly User $user,
        #[Attribute('feedback_subscription_plan_name')]
        private readonly FeedbackSubscriptionPlanName $subscriptionPlan,
        #[Attribute('subscription_expire_at')]
        private readonly DateTimeInterface $expireAt,
        private readonly ?MessengerUser $messengerUser,
        private readonly ?TelegramBotPayment $telegramPayment,
    )
    {
        $this->userId = $this->user->getId();
        $this->messengerUserId = $this->messengerUser?->getId();
        $this->telegramPaymentId = $this->telegramPayment?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setMessengerUserId(?string $messengerUserId): self
    {
        $this->messengerUserId = $messengerUserId;
        return $this;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSubscriptionPlan(): FeedbackSubscriptionPlanName
    {
        return $this->subscriptionPlan;
    }

    public function setTelegramPaymentId(?string $telegramPaymentId): self
    {
        $this->telegramPaymentId = $telegramPaymentId;
        return $this;
    }

    public function getTelegramPaymentId(): ?string
    {
        return $this->telegramPaymentId;
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
}
