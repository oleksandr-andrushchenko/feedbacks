<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Factory\Feedback\FeedbackNotificationFactory;
use App\Message\Command\Feedback\NotifyFeedbackSearchSourcesAboutNewFeedbackCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\FeedbackSearchService;
use App\Service\Feedback\FeedbackService;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Search\Viewer\Telegram\FeedbackTelegramSearchViewer;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see NotifyFeedbackSearchSourcesAboutNewFeedbackCommandHandler
 */
class NotifyFeedbackSearchSourcesAboutNewFeedbackCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramSearchViewer $feedbackTelegramSearchViewer,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackNotificationFactory $feedbackNotificationFactory,
        private readonly EntityManager $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly FeedbackService $feedbackService,
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchSourcesAboutNewFeedbackCommand $command): void
    {
        $feedback = $command->getFeedback() ?? $this->feedbackRepository->find($command->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $command->getFeedbackId()));
            return;
        }

        $messengerUser = $this->feedbackService->getMessengerUser($feedback);

        foreach ($this->feedbackService->getSearchTerms($feedback) as $searchTerm) {
            $feedbackSearches = $this->feedbackSearchSearcher->searchFeedbackSearches($searchTerm);

            foreach ($feedbackSearches as $feedbackSearch) {
                $messengerUser_ = $this->feedbackSearchService->getMessengerUser($feedbackSearch);

                if (
                    $messengerUser_ !== null
                    && $messengerUser_->getMessenger() === Messenger::telegram
                    && $messengerUser_->getId() !== $messengerUser->getId()
                ) {
                    $this->notify($messengerUser_, $searchTerm, $feedback, $feedbackSearch);
                }
            }
        }
    }


    private function notify(
        MessengerUser $messengerUser,
        SearchTerm $searchTerm,
        Feedback $feedback,
        FeedbackSearch $feedbackSearch
    ): void
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
                $this->getNotifyMessage($messengerUser, $bot, $feedback),
                keepKeyboard: true
            );

            $notification = $this->feedbackNotificationFactory->createFeedbackNotification(
                FeedbackNotificationType::feedback_search_source_about_new_feedback,
                $messengerUser,
                $searchTerm,
                feedback: $feedback,
                feedbackSearch: $feedbackSearch,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, Feedback $feedback): string
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        $localeCode = $user->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackTelegramSearchViewer->getFeedbackTelegramView(
            $bot,
            $feedback,
            addSecrets: $user?->getSubscriptionExpireAt() < new DateTimeImmutable(),
            addCountry: true,
            addTime: true,
            addQuotes: true,
            locale: $localeCode
        );

        return $message;
    }
}