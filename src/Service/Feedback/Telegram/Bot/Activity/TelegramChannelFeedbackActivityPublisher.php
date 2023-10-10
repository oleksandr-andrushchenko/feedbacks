<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Activity;

use App\Entity\Feedback\Feedback;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSender;
use App\Service\Telegram\Bot\TelegramBot;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class TelegramChannelFeedbackActivityPublisher
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramBotMessageSender $messageSender,
        private readonly FeedbackTelegramViewProvider $viewProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function publishTelegramChannelFeedbackActivity(TelegramBot $bot, Feedback $feedback): void
    {
        $channels = $this->repository->findPrimaryByGroupAndCountry(
            $bot->getEntity()->getGroup(),
            $bot->getEntity()->getCountryCode()
        );

        foreach ($channels as $channel) {
            try {
                $message = $this->viewProvider->getFeedbackTelegramView(
                    $bot,
                    $feedback,
                    localeCode: $bot->getEntity()->getLocaleCode(),
                    showTime: false,
                    channel: $channel,
                );
                $chatId = '@' . $channel->getUsername();

                $response = $this->messageSender->sendTelegramMessage(
                    $bot->getEntity(),
                    $chatId,
                    $message,
                    keepKeyboard: true
                );

                if (!$response->isOk()) {
                    throw new RuntimeException($response->getDescription());
                }

                $messageId = $response->getResult()?->getMessageId();

                if ($messageId !== null) {
                    $feedback->addChannelMessageId($messageId);
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }
    }
}