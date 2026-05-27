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

    public function createTelegramConversation(string $conversationClass): TelegramBotConversationInterface
    {
        return $this->conversationServiceLocator->get(array_search($conversationClass, $this->getTelegramConversations()));
    }

    /**
     * @return array|string[]|TelegramBotConversationInterface[]
     */
    public function getTelegramConversations(): array
    {
        return $this->conversationServiceLocator->getProvidedServices();
    }
}
