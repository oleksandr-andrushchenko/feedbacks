<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Entity\Telegram\TelegramBotCommandInterface;
use App\Entity\Telegram\TelegramBotPayment;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationFactory;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

abstract class TelegramBotGroup implements TelegramBotGroupInterface
{
    public function __construct(
        private readonly TelegramBotAwareHelper $awareHelper,
        protected readonly TelegramBotConversationFactory $conversationFactory,
    )
    {
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return TelegramBotCommandInterface[]
     */
    abstract protected function getCommands(TelegramBotAwareHelper $tg): iterable;

    /**
     * @param TelegramBot $bot
     * @return array|TelegramBotCommandInterface[]
     */
    final public function getTelegramCommands(TelegramBot $bot): array
    {
        $tg = $this->awareHelper->withTelegramBot($bot);

        return iterator_to_array($this->getCommands($tg));
    }

    final public function getTelegramConversationFactory(): TelegramBotConversationFactory
    {
        return $this->conversationFactory;
    }

    abstract protected function acceptPayment(TelegramBotPayment $payment, TelegramBotAwareHelper $tg): void;

    final public function acceptTelegramPayment(TelegramBot $bot, TelegramBotPayment $payment): void
    {
        $tg = $this->awareHelper->withTelegramBot($bot);
        $this->acceptPayment($payment, $tg);
    }

    abstract protected function supportsUpdate(TelegramBotAwareHelper $tg): bool;

    final public function supportsTelegramUpdate(TelegramBot $bot): bool
    {
        $tg = $this->awareHelper->withTelegramBot($bot);

        return $this->supportsUpdate($tg);
    }
}