<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Factory\Feedback\FeedbackNotificationFactory;
use App\Message\Command\Feedback\NotifyFeedbackTargetAboutNewFeedbackCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTermService;
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
 * @see NotifyFeedbackTargetAboutNewFeedbackCommandHandler
 */
class NotifyFeedbackTargetAboutNewFeedbackCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly LoggerInterface $logger,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramSearchViewer $feedbackTelegramSearchViewer,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackNotificationFactory $feedbackNotificationFactory,
        private readonly EntityManager $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly SearchTermService $searchTermService,
        private readonly FeedbackService $feedbackService,
    )
    {
    }

    public function __invoke(NotifyFeedbackTargetAboutNewFeedbackCommand $command): void
    {
        $feedback = $command->getFeedback() ?? $this->feedbackRepository->find($command->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $command->getFeedbackId()));
            return;
        }

        foreach ($this->feedbackService->getSearchTerms($feedback) as $searchTerm) {
            $messengerUser = $this->searchTermService->getMessengerUser($searchTerm);

            if (
                $messengerUser !== null
                && $messengerUser->getMessenger() === Messenger::telegram
                && $messengerUser->getId() !== $this->feedbackService->getMessengerUser($feedback)->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedback);
                return;
            }

            // todo: process usernames from MessengerUser::$usernameHistory
            // todo: add search across unknown types (check if telegram type in types -> normalize text -> search)

            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if ($messenger === Messenger::telegram) {
                $username = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

                $messengerUser = $this->messengerUserRepository->findOneByMessengerAndUsername($messenger, $username);

                if (
                    $messengerUser !== null
                    && $messengerUser->getId() !== $this->feedbackService->getMessengerUser($feedback)->getId()
                ) {
                    $this->notify($messengerUser, $searchTerm, $feedback);
                }
            }
        }
    }

    private function notify(MessengerUser $messengerUser, SearchTerm $searchTerm, Feedback $feedback): void
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
                FeedbackNotificationType::feedback_target_about_new_feedback,
                $messengerUser,
                $searchTerm,
                feedback: $feedback,
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