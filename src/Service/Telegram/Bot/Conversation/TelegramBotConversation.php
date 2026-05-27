<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

abstract class TelegramBotConversation
{
    public function __construct(
        protected TelegramBotConversationState $state,
    )
    {
    }

    abstract public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void;

    public function getState(): TelegramBotConversationState
    {
        return $this->state;
    }

    public function setState(TelegramBotConversationState $state): void
    {
        $this->state = $state;
    }
}