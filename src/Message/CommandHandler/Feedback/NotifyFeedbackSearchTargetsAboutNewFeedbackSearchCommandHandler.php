<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Factory\Feedback\FeedbackNotificationFactory;
use App\Message\Command\Feedback\NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\Feedback\FeedbackSearchService;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTermService;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Search\Viewer\Telegram\SearchRegistryTelegramSearchViewer;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommandHandler
 */
class NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly SearchRegistryTelegramSearchViewer $searchRegistryTelegramSearchViewer,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackNotificationFactory $feedbackNotificationFactory,
        private readonly EntityManager $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly SearchTermService $searchTermService,
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommand $command): void
    {
        $feedbackSearch = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($feedbackSearch === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $searchTerm = $this->feedbackSearchService->getSearchTerm($feedbackSearch);
        $messengerUser = $this->feedbackSearchService->getMessengerUser($feedbackSearch);
        $messengerUser_ = $this->searchTermService->getMessengerUser($searchTerm);

        if (
            $messengerUser_ !== null
            && $messengerUser_->getMessenger() === Messenger::telegram
            && $messengerUser_->getId() !== $messengerUser->getId()
        ) {
            $this->notify($messengerUser_, $searchTerm, $feedbackSearch);
            return;
        }

        // todo: process usernames from MessengerUser::$usernameHistory
        // todo: add search across unknown types (check if telegram type in types -> normalize text -> search)

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

        if ($messenger === Messenger::telegram) {
            $username = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

            $messengerUser_ = $this->messengerUserRepository->findOneByMessengerAndUsername($messenger, $username);

            if (
                $messengerUser_ !== null
                && $messengerUser_->getId() !== $messengerUser->getId()
            ) {
                $this->notify($messengerUser_, $searchTerm, $feedbackSearch);
            }
        }
    }

    private function notify(MessengerUser $messengerUser, SearchTerm $searchTerm, FeedbackSearch $feedbackSearch): void
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
                $this->getNotifyMessage($messengerUser, $bot, $feedbackSearch),
                keepKeyboard: true
            );

            $notification = $this->feedbackNotificationFactory->createFeedbackNotification(
                FeedbackNotificationType::feedback_search_target_about_new_feedback_search,
                $messengerUser,
                $searchTerm,
                feedbackSearch: $feedbackSearch,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackSearch $feedbackSearch): string
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        $localeCode = $user->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->searchRegistryTelegramSearchViewer->getFeedbackSearchTelegramView(
            $bot,
            $feedbackSearch,
            addSecrets: $user?->getSubscriptionExpireAt() < new DateTimeImmutable(),
            addCountry: true,
            addTime: true,
            addQuotes: true,
            locale: $localeCode
        );

        return $message;
    }
}