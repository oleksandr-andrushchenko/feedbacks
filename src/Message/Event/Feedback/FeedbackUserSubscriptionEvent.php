<?php
declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use LogicException;

abstract readonly class FeedbackUserSubscriptionEvent
{
    private ?string $subscriptionId;

    public function __construct(
        ?string $subscriptionId = null,
        private ?FeedbackUserSubscription $subscription = null,
    )
    {
        if ($subscriptionId === null) {
            if ($this->subscription === null) {
                throw new LogicException('Either subscription id or subscription should be passed`');
            }

            $this->subscriptionId = $this->subscription->getId();
        } else {
            $this->subscriptionId = $subscriptionId;
        }
    }

    public function getSubscription(): ?FeedbackUserSubscription
    {
        return $this->subscription;
    }

    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function __sleep(): array
    {
        return [
            'subscriptionId',
        ];
    }
}
