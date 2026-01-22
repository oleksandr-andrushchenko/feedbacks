<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Entity\Telegram\TelegramBotPayment;
use App\Model\Telegram\TelegramBotHandlerInterface;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationFactory;
use App\Service\Telegram\Bot\TelegramBot;

interface TelegramBotGroupInterface
{
    /**
     * @param TelegramBot $bot
     * @return TelegramBotHandlerInterface[]
     */
    public function getTelegramHandlers(TelegramBot $bot): iterable;

    public function getTelegramConversationFactory(): TelegramBotConversationFactory;

    public function acceptTelegramPayment(TelegramBot $bot, TelegramBotPayment $payment): void;

    public function supportsTelegramUpdate(TelegramBot $bot): bool;
}