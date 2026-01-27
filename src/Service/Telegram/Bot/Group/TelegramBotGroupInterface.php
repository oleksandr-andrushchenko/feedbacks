<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Entity\Telegram\TelegramBotPayment;
use App\Model\Telegram\TelegramBotHandlerInterface;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationFactory;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

interface TelegramBotGroupInterface
{
    public function getHandlers(TelegramBotAwareHelper $tg): iterable;

    public function getTelegramConversationFactory(): TelegramBotConversationFactory;

    public function acceptPayment(TelegramBotPayment $payment, TelegramBotAwareHelper $tg): void;

    public function supportsUpdate(TelegramBotAwareHelper $tg): bool;
}