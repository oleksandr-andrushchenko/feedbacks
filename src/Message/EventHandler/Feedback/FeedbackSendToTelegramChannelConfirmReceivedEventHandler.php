<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\Telegram\TelegramChannel;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Messenger\MessengerUserService;
use App\Service\Search\Viewer\Telegram\FeedbackTelegramSearchViewer;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use App\Service\Telegram\Channel\View\TelegramChannelLinkViewProvider;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSendToTelegramChannelConfirmReceivedEventHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly TelegramChannelMatchesProvider $telegramChannelMatchesProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly FeedbackTelegramSearchViewer $feedbackTelegramSearchViewer,
        private readonly TelegramChannelLinkViewProvider $telegramChannelLinkViewProvider,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly MessengerUserService $messengerUserService,
        private readonly FeedbackService $feedbackService,
    )
    {
    }

    public function __invoke(FeedbackSendToTelegramChannelConfirmReceivedEvent $event): void
    {
        $feedback = $event->getFeedback() ?? $this->feedbackRepository->find($event->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $bot = $feedback->getTelegramBot();

        if ($bot === null) {
            $this->logger->notice(sprintf('No telegram bot was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $addTime = $event->addTime();
        $notifyUser = $event->notifyUser();
        $user = $this->feedbackService->getUser($feedback);
        $channels = $this->telegramChannelMatchesProvider->getCachedTelegramChannelMatches($user, $bot);

        foreach ($channels as $channel) {
            $message = $this->feedbackTelegramSearchViewer->getFeedbackTelegramView(
                $bot,
                $feedback,
                addSecrets: true,
                addSign: true,
                addCountry: true,
                addTime: $addTime,
                channel: $channel,
                locale: $channel->getLocaleCode(),
            );

            $chatId = $channel->getChatId() ?? ('@' . $channel->getUsername());

            $response = $this->telegramBotMessageSender->sendTelegramMessage($bot, $chatId, $message, keepKeyboard: true);

            if (!$response->isOk()) {
                $this->logger->error($response->getDescription());
                continue;
            }

            $messageId = $response->getResult()?->getMessageId();

            if ($messageId !== null) {
                $feedback->addTelegramChannelMessageId($messageId);
            }
        }

        if ($notifyUser) {
            $messengerUser = $this->feedbackService->getMessengerUser($feedback);
            $user = $this->messengerUserService->getUser($messengerUser);
            $userLocaleCode = $user->getLocaleCode();
            $userChatId = $messengerUser->getIdentifier();
            $searchTerms = $this->feedbackService->getSearchTerms($feedback);
            $searchTermView = $this->multipleSearchTermTelegramViewProvider->getFeedbackSearchTermsTelegramView($searchTerms, locale: $userLocaleCode);
            $channelViews = implode(
                ', ',
                array_map(
                    fn (TelegramChannel $channel): string => $this->telegramChannelLinkViewProvider->getTelegramChannelLinkView($channel, html: true),
                    $channels
                )
            );
            $parameters = [
                'search_term' => $searchTermView,
                'channels' => $channelViews,
            ];
            $message = '🫡 ';
            $message .= $this->translator->trans('feedback_published', parameters: $parameters, domain: 'feedbacks.tg.notify', locale: $userLocaleCode);

            $this->telegramBotMessageSender->sendTelegramMessage($bot, $userChatId, $message, keepKeyboard: true);
        }
    }
}