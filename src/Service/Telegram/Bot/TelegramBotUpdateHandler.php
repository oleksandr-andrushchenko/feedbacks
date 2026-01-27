<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotUpdate;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentNotFoundException;
use App\Exception\Telegram\Bot\Payment\TelegramBotUnknownPaymentException;
use App\Exception\Telegram\Bot\TelegramBotInvalidUpdateException;
use App\Model\Telegram\TelegramBotErrorHandler;
use App\Model\Telegram\TelegramBotFallbackHandler;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Repository\Telegram\Bot\TelegramBotUpdateRepository;
use App\Service\ORM\EntityManager;
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
        private readonly TelegramBotUpdateRepository $telegramBotUpdateRepository,
        private readonly TelegramBotUserProvider $telegramBotUserProvider,
        private readonly TelegramBotConversationManager $telegramBotConversationManager,
        private readonly TelegramBotMessengerUserUpserter $telegramBotMessengerUserUpserter,
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
        private readonly TelegramBotPaymentManager $telegramBotPaymentManager,
        private readonly TelegramBotLocaleSwitcher $telegramBotLocaleSwitcher,
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotAwareHelper $telegramBotAwareHelper,
        private readonly TelegramBotLinkViewProvider $telegramBotLinkViewProvider,
        private readonly EntityManager $entityManager,
        private readonly LoggerInterface $logger,
        private readonly bool $checkDuplicates = false,
        private readonly bool $saveRequests = false,
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

        // non-admin update checker
        // todo: remove on production
        if ($bot->getEntity()->adminOnly()) {
            $currentUser = $this->telegramBotUserProvider->getTelegramUserByUpdate($bot->getUpdate());

            if (!in_array($currentUser?->getId(), $bot->getEntity()->getAdminIds(), true)) {
                return;
            }
        }

        // update checker
        if ($bot->getEntity()->checkUpdates()) {
            if ($this->checkDuplicates) {
                $update = $this->telegramBotUpdateRepository->findOneByUpdateId($bot->getUpdate()?->getUpdateId());

                if ($update !== null) {
                    $this->logger->warning('Duplicate telegram update received, processing aborted', [
                        'name' => $bot->getEntity()->getGroup()->name,
                        'update_id' => $update->getId(),
                    ]);

                    return;
                }
            }

            if ($this->saveRequests) {
                $update = new TelegramBotUpdate(
                    (string) $bot->getUpdate()->getUpdateId(),
                    $bot->getUpdate()->getRawData(),
                    $bot->getEntity(),
                );
                $this->entityManager->persist($update);
            }
        }

        $messengerUser = $this->telegramBotMessengerUserUpserter->upsertTelegramMessengerUser($bot);

        $bot->setMessengerUser($messengerUser);
        $this->telegramBotLocaleSwitcher->syncLocale($bot, $request);

        TelegramLog::initialize($this->logger, $this->logger);

        $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());
        $tg = $this->telegramBotAwareHelper->withTelegramBot($bot);
        $handlers = iterator_to_array($group->getHandlers($tg));

        try {
            if ($update->getPreCheckoutQuery() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $this->telegramBotPaymentManager->acceptPreCheckoutQuery($bot, $update->getPreCheckoutQuery());
                }
                return;
            } elseif ($update->getMessage()?->getSuccessfulPayment() !== null) {
                if (!$bot->deleted() && $bot->getEntity()->acceptPayments()) {
                    $payment = $this->telegramBotPaymentManager->acceptSuccessfulPayment($bot, $update->getMessage()->getSuccessfulPayment());
                    $group->acceptPayment($payment, $tg);
                }
                return;
            }

            if (!$group->supportsUpdate($tg)) {
                return;
            }

            if ($bot->deleted() || !$bot->primary()) {
                $newBot = $this->telegramBotRepository->findOnePrimaryNonDeletedByBot($bot->getEntity());

                if ($newBot === null) {
                    $this->logger->warning('Primary bot has not been found to replace deleted/non-primary one', [
                        'bot_id' => $bot->getEntity()->getId(),
                    ]);
                } else {
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


            $handled = false;

            // if /command typed = force it
            foreach ($handlers as $handler) {
                if (call_user_func_array($handler->getSupports(), [$bot->getUpdate(), true]) === true) {
                    call_user_func($handler->getCallback());
                    $handled = true;
                }
            }

            // if conversation exists = continue it
            if (!$handled) {
                $conversation = $this->telegramBotConversationManager->getCurrentTelegramConversation($bot);
                if ($conversation !== null) {
                    $this->telegramBotConversationManager->continueTelegramConversation($bot, $conversation);
                    $handled = true;
                }
            }

            // if /command typed = start it
            if (!$handled) {
                foreach ($handlers as $handler) {
                    if (call_user_func_array($handler->getSupports(), [$bot->getUpdate(), false]) === true) {
                        call_user_func($handler->getCallback());
                        $handled = true;
                    }
                }
            }

            // fallback
            if (!$handled) {
                foreach ($handlers as $handler) {
                    if ($handler instanceof TelegramBotFallbackHandler) {
                        call_user_func($handler->getCallback());
                    }
                }
            }
        } catch (Throwable $exception) {
            if ($this->environment === 'test') {
                throw $exception;
            }
            $this->logger->error($exception);

            // error
            foreach ($handlers as $handler) {
                if ($handler instanceof TelegramBotErrorHandler) {
                    call_user_func($handler->getCallback(), $exception);
                }
            }
        }
    }
}