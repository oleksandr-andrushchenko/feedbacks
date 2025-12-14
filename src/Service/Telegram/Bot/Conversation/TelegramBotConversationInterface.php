<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;

interface TelegramBotConversationInterface
{
    public function getState(): TelegramBotConversationState;

    public function setState(TelegramBotConversationState $state): static;

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null;
}