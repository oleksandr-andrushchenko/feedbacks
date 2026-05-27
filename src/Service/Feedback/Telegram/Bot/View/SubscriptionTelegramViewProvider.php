<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\View;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Intl\TimeProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

class SubscriptionTelegramViewProvider
{
    public function __construct(
        private readonly TimeProvider $timeProvider,
        private readonly MoneyFormatter $moneyFormatter,
        private readonly FeedbackSubscriptionPlanProvider $feedbackSubscriptionPlanProvider,
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
    )
    {
    }

    public function getSubscriptionTelegramView(
        TelegramBotAwareHelper $tg,
        FeedbackUserSubscription $subscription,
        int $number = null
    ): string
    {
        $subscriptionPlan = $subscription->getSubscriptionPlan();
        $telegramPayment = $subscription->getTelegramPayment();

        $parameters = [
            'number' => $number,
            'subscription_plan' => $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName($subscriptionPlan),
            'is_subscription_active' => $this->feedbackSubscriptionManager->isSubscriptionActive($subscription),
            'period' => $this->timeProvider->formatIntervalAsShortDate(
                $subscription->getCreatedAt(),
                $subscription->getExpireAt(),
                timezone: $tg->getTimezone(),
                locale: $tg->getLocaleCode()
            ),
        ];

        if ($telegramPayment !== null) {
            $price = $subscription->getTelegramPayment()->getPrice();

            $parameters['price'] = $this->moneyFormatter->formatMoney($price, native: true);
        }

        return $tg->view('subscription', $parameters);
    }
}