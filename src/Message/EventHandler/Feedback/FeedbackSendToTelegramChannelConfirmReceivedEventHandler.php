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
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

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

        $bot = $this->feedbackService->getTelegramBot($feedback);

        if ($bot === null) {
            $this->logger->warning(sprintf('No telegram bot was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $addTime = $event->addTime();
        $notifyUser = $event->notifyUser();
        $user = $this->feedbackService->getUser($feedback);
        $channels = $this->telegramChannelMatchesProvider->getCachedTelegramChannelMatches($user, $bot);

        if (count($channels) === 0) {
            $this->logger->warning(sprintf('No telegram channels were found in %s for %s bot id', __CLASS__, $bot->getId()));
            return;
        }

        $failedChannelIds = [];

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

            try {
                if ($feedback->hasMedia() && mb_strlen($message) <= 1024) {
                    $response = $this->telegramBotMessageSender->sendTelegramMessage(
                        $bot,
                        $chatId,
                        $message,
                        keepKeyboard: true,
                        media: $feedback->getMedia()
                    );
                } else {
                    $response = $this->telegramBotMessageSender->sendTelegramMessage($bot, $chatId, $message, keepKeyboard: true);
                }

                if (!$response->isOk()) {
                    $this->logger->error($response->getDescription());
                    continue;
                }

                foreach ($this->getMessageIds($response) as $messageId) {
                    $feedback->addTelegramChannelMessageId($messageId);
                }

                if ($feedback->hasMedia() && mb_strlen($message) > 1024) {
                    $response = $this->telegramBotMessageSender->sendTelegramMessage(
                        $bot,
                        $chatId,
                        '',
                        keepKeyboard: true,
                        media: $feedback->getMedia()
                    );

                    if (!$response->isOk()) {
                        $this->logger->error($response->getDescription());
                        continue;
                    }

                    foreach ($this->getMessageIds($response) as $messageId) {
                        $feedback->addTelegramChannelMessageId($messageId);
                    }
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception, [
                    'channel_id' => $channel->getId(),
                    'chat_id' => $chatId,
                ]);
                $failedChannelIds[] = $channel->getId();
            }
        }

        $channels = array_values(
            array_filter($channels, static fn (TelegramChannel $channel) => !in_array($channel->getId(), $failedChannelIds, true))
        );

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

    /**
     * @return int[]
     */
    private function getMessageIds(ServerResponse $response): array
    {
        $result = $response->getResult();

        if ($result instanceof Message) {
            return array_filter([$result->getMessageId()]);
        }

        if (is_array($result)) {
            return array_values(array_filter(array_map(
                static fn ($item): ?int => $item instanceof Message ? $item->getMessageId() : null,
                $result
            )));
        }

        return [];
    }
}
