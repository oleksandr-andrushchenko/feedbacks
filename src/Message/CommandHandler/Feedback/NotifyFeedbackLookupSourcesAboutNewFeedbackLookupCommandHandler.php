<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Factory\Feedback\FeedbackNotificationFactory;
use App\Message\Command\Feedback\NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Service\Feedback\FeedbackLookupSearcher;
use App\Service\Feedback\Telegram\Bot\View\FeedbackLookupTelegramViewProvider;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommandHandler
 */
class NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommandHandler
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackLookupSearcher $feedbackLookupSearcher,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackLookupTelegramViewProvider $feedbackLookupTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackNotificationFactory $feedbackNotificationFactory,
        private readonly EntityManager $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
    )
    {
    }

    public function __invoke(NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommand $command): void
    {
        $feedbackLookup = $command->getFeedbackLookup() ?? $this->feedbackLookupRepository->find($command->getFeedbackLookupId());

        if ($feedbackLookup === null) {
            $this->logger->warning(sprintf('No feedback lookup was found in %s for %s id', __CLASS__, $command->getFeedbackLookupId()));
            return;
        }

        $searchTerm = $feedbackLookup->getSearchTerm();
        $targetFeedbackLookups = $this->feedbackLookupSearcher->searchFeedbackLookups($searchTerm);

        foreach ($targetFeedbackLookups as $targetFeedbackLookup) {
            $messengerUser = $targetFeedbackLookup->getMessengerUser();

            if (
                $messengerUser !== null
                && $messengerUser->getMessenger() === Messenger::telegram
                && $messengerUser->getId() !== $feedbackLookup->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedbackLookup, $targetFeedbackLookup);
            }
        }
    }


    private function notify(
        MessengerUser $messengerUser,
        SearchTerm $searchTerm,
        FeedbackLookup $feedbackLookup,
        FeedbackLookup $targetFeedbackLookup
    ): void
    {
        $botIds = $messengerUser->getBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotProvider->getCachedTelegramBotsByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedbackLookup),
                keepKeyboard: true
            );

            $notification = $this->feedbackNotificationFactory->createFeedbackNotification(
                FeedbackNotificationType::feedback_lookup_source_about_new_feedback_lookup,
                $messengerUser,
                $searchTerm,
                feedbackLookup: $feedbackLookup,
                targetFeedbackLookup: $targetFeedbackLookup,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackLookup $feedbackLookup): string
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        $localeCode = $user->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackLookupTelegramViewProvider->getFeedbackLookupTelegramView(
            $bot,
            $feedbackLookup,
            addSecrets: $user?->getSubscriptionExpireAt() < new DateTimeImmutable(),
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}