<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use App\Model\Telegram\TelegramBotConversationState;

abstract class TelegramBotConversation implements TelegramBotConversationInterface
{
    public function __construct(
        protected TelegramBotConversationState $state,
    )
    {
    }

    public function getState(): TelegramBotConversationState
    {
        return $this->state;
    }

    public function setState(TelegramBotConversationState $state): static
    {
        $this->state = $state;

        return $this;
    }
}