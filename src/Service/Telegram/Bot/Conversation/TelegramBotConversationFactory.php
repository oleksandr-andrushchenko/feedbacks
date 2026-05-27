<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use Symfony\Component\DependencyInjection\ServiceLocator;

class TelegramBotConversationFactory
{
    public function __construct(
        private readonly ServiceLocator $conversationServiceLocator,
    )
    {
    }

    public function createTelegramConversation(string $conversationClass): TelegramBotConversation
    {
        return $this->conversationServiceLocator->get(array_search($conversationClass, $this->getTelegramConversations()));
    }

    /**
     * @return array|string[]|TelegramBotConversation[]
     */
    public function getTelegramConversations(): array
    {
        return $this->conversationServiceLocator->getProvidedServices();
    }
}
