<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Telegram\Bot\View\SubscriptionTelegramViewProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

class SubscriptionsTelegramChatSender
{
    public function __construct(
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
        private readonly SubscriptionTelegramViewProvider $subscriptionTelegramViewProvider
    )
    {
    }

    public function sendFeedbackSubscriptions(TelegramBotAwareHelper $tg): null
    {
        $messengerUser = $tg->getBot()->getMessengerUser();
        $subscriptions = $this->feedbackSubscriptionManager->getSubscriptions($messengerUser);

        $count = count($subscriptions);

        if ($count === 0) {
            $message = $tg->trans('empty_list', domain: 'subscriptions');
            $message = $tg->upsetText($message);

            return $tg->reply($message)->null();
        }

        $parameters = [
            'count' => $count,
        ];
        $message = $tg->trans('title', $parameters, domain: 'subscriptions');

        $tg->reply($message);

        foreach (array_reverse($subscriptions, true) as $index => $subscription) {
            $message = $this->subscriptionTelegramViewProvider->getSubscriptionTelegramView($tg, $subscription, $index + 1);

            $tg->reply($message);
        }

        return null;
    }
}
