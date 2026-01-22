<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentNotFoundException;
use App\Exception\Telegram\Bot\Payment\TelegramBotUnknownPaymentException;
use App\Exception\Telegram\Bot\TelegramBotInvalidUpdateException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentManager;
use App\Service\Telegram\Bot\View\TelegramBotLinkViewProvider;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class TelegramBotUpdateHandler
{
    public function __construct(
        private readonly string $environment,
        private readonly TelegramBotUpdateFactory $telegramBotUpdateFactory,
        private readonly TelegramBotUpdateChecker $telegramBotUpdateChecker,
        private readonly TelegramBotNonAdminUpdateChecker $telegramBotNonAdminUpdateChecker,
        private readonly TelegramBotConversationManager $telegramBotConversationManager,
        private readonly TelegramBotMessengerUserUpserter $telegramBotMessengerUserUpserter,
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
        private readonly TelegramBotHandlerFinder $telegramBotHandlerFinder,
        private readonly TelegramBotPaymentManager $telegramBotPaymentManager,
        private readonly TelegramBotLocaleSwitcher $telegramBotLocaleSwitcher,
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotAwareHelper $telegramBotAwareHelper,
        private readonly TelegramBotLinkViewProvider $telegramBotLinkViewProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param TelegramBot $botEntity
     * @param Request $request
     * @return void
     * @throws TelegramBotInvalidUpdateException
     * @throws TelegramBotPaymentNotFoundException
     * @throws Throwable
     * @throws TelegramBotUnknownPaymentException
     */
    public function handleTelegramBotUpdate(TelegramBot $botEntity, Request $request): void
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);
        $update = $this->telegramBotUpdateFactory->createUpdate($bot, $request);
        $bot->setUpdate($update);

        // todo: remove on production
        if ($this->telegramBotNonAdminUpdateChecker->checkNonAdminUpdate($bot)) {
            return;
        }

        if ($this->telegramBotUpdateChecker->checkTelegramUpdate($bot)) {
            return;
        }

        $messengerUser = $this->telegramBotMessengerUserUpserter->upsertTelegramMessengerUser($bot);

        $bot->setMessengerUser($messengerUser);
        $this->telegramBotLocaleSwitcher->syncLocale($bot, $request);

        TelegramLog::initialize($this->logger, $this->logger);

        $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());
        $handlers = $group->getTelegramHandlers($bot);

        try {
            if ($update->getPreCheckoutQuery() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $this->telegramBotPaymentManager->acceptPreCheckoutQuery($bot, $update->getPreCheckoutQuery());
                }
                return;
            } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $payment = $this->telegramBotPaymentManager->acceptSuccessfulPayment($bot, $update->getMessage()->getSuccessfulPayment());
                    $group->acceptTelegramPayment($bot, $payment);
                }
                return;
            }

            if (!$group->supportsTelegramUpdate($bot)) {
                return;
            }

            if ($bot->deleted() || !$bot->primary()) {
                $newBot = $this->telegramBotRepository->findOnePrimaryNonDeletedByBot($bot->getEntity());

                if ($newBot === null) {
                    $this->logger->warning('Primary bot has not been found to replace deleted/non-primary one', [
                        'bot_id' => $bot->getEntity()->getId(),
                    ]);
                } else {
                    $tg = $this->telegramBotAwareHelper->withTelegramBot($bot);
                    $tg->reply(
                        $tg->attentionText(
                            sprintf(
                                "%s:\n\n%s",
                                $tg->trans('reply.use_primary'),
                                $this->telegramBotLinkViewProvider->getTelegramBotLinkView($newBot)
                            )
                        )
                    );
                }

                return;
            }

            if ($handler = $this->telegramBotHandlerFinder->findOneHandler($bot->getUpdate(), $handlers, force: true)) {
                call_user_func($handler->getCallback());
            } elseif ($conversation = $this->telegramBotConversationManager->getCurrentTelegramConversation($bot)) {
                $this->telegramBotConversationManager->continueTelegramConversation($bot, $conversation);
            } elseif ($handler = $this->telegramBotHandlerFinder->findOneHandler($bot->getUpdate(), $handlers)) {
                call_user_func($handler->getCallback());
            } elseif ($handler = $this->telegramBotHandlerFinder->findOneFallbackHandler($handlers)) {
                call_user_func($handler->getCallback());
            }
        } catch (Throwable $exception) {
            if ($this->environment === 'test') {
                throw $exception;
            }
            $this->logger->error($exception);

            if ($handler = $this->telegramBotHandlerFinder->findOneErrorHandler($handlers)) {
                call_user_func($handler->getCallback(), $exception);
            }
        }
    }
}