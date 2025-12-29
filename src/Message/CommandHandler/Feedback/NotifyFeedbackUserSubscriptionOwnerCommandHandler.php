<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Factory\Feedback\FeedbackNotificationFactory;
use App\Message\Command\Feedback\NotifyFeedbackUserSubscriptionOwnerCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Messenger\MessengerUserService;
use App\Service\Modifier;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see NotifyFeedbackUserSubscriptionOwnerCommandHandler
 */
class NotifyFeedbackUserSubscriptionOwnerCommandHandler
{
    public function __construct(
        private readonly FeedbackUserSubscriptionRepository $feedbackUserSubscriptionRepository,
        private readonly LoggerInterface $logger,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackNotificationFactory $feedbackNotificationFactory,
        private readonly EntityManager $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly TimeProvider $timeProvider,
        private readonly FeedbackSubscriptionPlanProvider $feedbackSubscriptionPlanProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly Modifier $modifier,
        private readonly MessengerUserService $messengerUserService,
    )
    {
    }

    public function __invoke(NotifyFeedbackUserSubscriptionOwnerCommand $command): void
    {
        $subscription = $command->getSubscription() ?? $this->feedbackUserSubscriptionRepository->find($command->getSubscriptionId());

        if ($subscription === null) {
            $this->logger->warning(sprintf('No subscription was found in %s for %s id', __CLASS__, $command->getSubscriptionId()));
            return;
        }

        $messengerUser = $subscription->getMessengerUser();

        if ($messengerUser === null) {
            $messengerUsers = $this->messengerUserRepository->findByUser($subscription->getUser());
        } else {
            $messengerUsers = [$messengerUser];
        }

        foreach ($messengerUsers as $messengerUser) {
            $this->notify($messengerUser, $subscription);
        }
    }

    private function notify(MessengerUser $messengerUser, FeedbackUserSubscription $subscription): void
    {
        $botIds = $messengerUser->getTelegramBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotProvider->getCachedTelegramBotsByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $subscription),
                keepKeyboard: true
            );

            $notification = $this->feedbackNotificationFactory->createFeedbackNotification(
                FeedbackNotificationType::feedback_user_subscription_owner,
                $messengerUser,
                feedbackUserSubscription: $subscription,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, FeedbackUserSubscription $subscription): string
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        $locale = $user->getLocaleCode();

        $plan = $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName(
            $subscription->getSubscriptionPlan(),
            localeCode: $locale
        );
        $expireAt = $this->timeProvider->formatAsDatetime(
            $subscription->getExpireAt(),
            timezone: $user->getTimezone(),
            locale: $locale
        );
        $parameters = [
            'plan' => $plan,
            'expire_at' => $expireAt,
        ];

        $m = $this->modifier;

        $domain = 'feedbacks.tg.notify';

        return $m->create()
            ->add($m->appendModifier($this->translator->trans('greetings', domain: $domain, locale: $locale)))
            ->add($m->appendModifier(' '))
            ->add($m->appendModifier($this->translator->trans('subscription_created', parameters: $parameters, domain: $domain, locale: $locale)))
            ->add($m->newLineModifier(2))
            ->add($m->appendModifier('🌟 '))
            ->add($m->appendModifier($this->translator->trans('subscription_expire', parameters: $parameters, domain: $domain, locale: $locale)))
            ->add($m->appendModifier(' '))
            ->add($m->appendModifier($this->translator->trans('regards', domain: $domain, locale: $locale)))
            ->apply('🎉 ')
        ;
    }
}