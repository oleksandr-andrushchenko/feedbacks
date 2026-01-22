<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotRequest;
use App\Exception\Telegram\Bot\TelegramBotException;
use App\Repository\Telegram\Bot\TelegramBotChatRequestMinuteRateLimitDynamodbRepository;
use App\Repository\Telegram\Bot\TelegramBotChatRequestSecondRateLimitDynamodbRepository;
use App\Repository\Telegram\Bot\TelegramBotGlobalRequestSecondRateLimitDynamodbRepository;
use App\Repository\Telegram\Bot\TelegramBotRequestDoctrineRepository;
use App\Service\IdGenerator;
use App\Service\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class TelegramBotRequestChecker
{
    public function __construct(
        private readonly TelegramBotChatRequestSecondRateLimitDynamodbRepository $telegramBotChatRequestSecondRateLimitDynamodbRepository,
        private readonly TelegramBotChatRequestMinuteRateLimitDynamodbRepository $telegramBotChatRequestMinuteRateLimitDynamodbRepository,
        private readonly TelegramBotGlobalRequestSecondRateLimitDynamodbRepository $telegramBotGlobalRequestSecondRateLimitDynamodbRepository,
        private readonly TelegramBotRequestDoctrineRepository $telegramBotRequestDoctrineRepository,
        private readonly EntityManager $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $checkRateLimits = false,
        private readonly bool $saveRequests = false,
        private readonly int $waitingTimeout = 60,
        private readonly int $intervalBetweenChecks = 1,
        private readonly array $methodWhitelist = [
            'sendMessage',
            'forwardMessage',
            'copyMessage',
            'sendPhoto',
            'sendAudio',
            'sendDocument',
            'sendSticker',
            'sendVideo',
            'sendAnimation',
            'sendVoice',
            'sendVideoNote',
            'sendMediaGroup',
            'sendLocation',
            'editMessageLiveLocation',
            'stopMessageLiveLocation',
            'sendVenue',
            'sendContact',
            'sendPoll',
            'sendDice',
            'sendInvoice',
            'sendGame',
            'setGameScore',
            'setMyCommands',
            'deleteMyCommands',
            'editMessageText',
            'editMessageCaption',
            'editMessageMedia',
            'editMessageReplyMarkup',
            'stopPoll',
            'setChatTitle',
            'setChatDescription',
            'setChatStickerSet',
            'deleteChatStickerSet',
            'setPassportDataErrors',
        ],
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @param string $method
     * @param mixed $data
     * @return TelegramBotRequest|null
     * @throws TelegramBotException
     */
    public function checkTelegramRequest(TelegramBot $bot, string $method, mixed $data): ?TelegramBotRequest
    {
        if (!$bot->getEntity()->checkRequests()) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $chatId = $data['chat_id'] ?? null;

        if ($chatId === null) {
            return null;
        }

        if (!in_array($method, $this->methodWhitelist, true)) {
            return null;
        }

        if ($this->checkRateLimits) {
            $timeout = $this->waitingTimeout;

            while (true) {
                if ($timeout <= 0) {
                    // todo: use specific
                    throw new TelegramBotException('Timed out while waiting for a request spot!');
                }

                if ($this->entityManager->getConfig()->isDynamodb()) {
                    $second = time();
                    $minute = intdiv($second, 60);

                    $globalSec = $this->telegramBotGlobalRequestSecondRateLimitDynamodbRepository->incrementCountBySecond($second);
                    $this->logger?->debug('$globalSec', [
                        'second' => $globalSec->getSecond(),
                        'count' => $globalSec->getCount(),
                        'expireAt' => $globalSec->getExpireAt()->getTimestamp(),
                        'skip' => $globalSec->getCount() > 30,
                    ]);

                    if ($globalSec->getCount() > 30) {
                        goto wait_and_retry;
                    }

                    $chatSec = $this->telegramBotChatRequestSecondRateLimitDynamodbRepository->incrementCountByChatAndSecond($chatId, $second);
                    $this->logger?->debug('$chatSec', [
                        'chatId' => $chatSec->getChatId(),
                        'second' => $chatSec->getSecond(),
                        'count' => $chatSec->getCount(),
                        'expireAt' => $chatSec->getExpireAt()->getTimestamp(),
                        'skip' => $chatSec->getCount() > 1,
                    ]);

                    if ($chatSec->getCount() > 1) {
                        goto wait_and_retry;
                    }

                    $chatMin = $this->telegramBotChatRequestMinuteRateLimitDynamodbRepository->incrementCountByChatAndMinute($chatId, $minute);
                    $this->logger?->debug('$chatMin', [
                        'chatId' => $chatMin->getChatId(),
                        'minute' => $chatMin->getMinute(),
                        'count' => $chatMin->getCount(),
                        'expireAt' => $chatMin->getExpireAt()->getTimestamp(),
                        'skip' => $chatMin->getCount() > 20,
                    ]);

                    if ($chatMin->getCount() > 20) {
                        goto wait_and_retry;
                    }

                    break;

                    wait_and_retry:
                    $timeout--;
                    usleep($this->intervalBetweenChecks * 1_000_000);
                } else {
                    $limits = $this->telegramBotRequestDoctrineRepository->getLimits($chatId);

                    if ($limits === null) {
                        break;
                    }

                    // No more than one message per second inside a particular chat
                    $chatPerSecond = $limits->getPerSecond() === 0;

                    // No more than 30 messages per second to different chats
                    $globalPerSecond = $limits->getPerSecondAll() < 30;

                    // No more than 20 messages per minute in groups and channels
                    $groupsPerMinute = $limits->getPerMinute() < 20;

                    if ($chatPerSecond && $globalPerSecond && $groupsPerMinute) {
                        break;
                    }

                    $timeout--;
                    usleep($this->intervalBetweenChecks * 1_000_000);
                }
            }
        }

        if ($this->saveRequests) {
            $request = new TelegramBotRequest(
                $this->idGenerator->generateId(),
                $method,
                $chatId,
                $data,
                $bot->getEntity(),
            );
            $this->entityManager->persist($request);

            return $request;
        }

        return null;
    }
}