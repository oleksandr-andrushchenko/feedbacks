<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Model\Telegram\TelegramBotCommandHandler;
use App\Model\Telegram\TelegramBotHandlerInterface;
use App\Model\Telegram\TelegramBotMyCommands;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeChat;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;

class TelegramBotMyCommandsProvider
{
    public function __construct(
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return TelegramBotMyCommands[]
     */
    public function getTelegramMyCommands(TelegramBot $bot): iterable
    {
        $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());

        $realCommandHandlers = array_values(
            array_filter(
                $group->getTelegramHandlers($bot),
                static fn (TelegramBotHandlerInterface $handler): bool => $handler instanceof TelegramBotCommandHandler
            )
        );

        $adminChatScopeCommandHandlers = array_values(
            array_filter(
                $realCommandHandlers,
                static fn (TelegramBotCommandHandler $commandHandler): bool => $commandHandler->isMenu()
            )
        );

        $defaultScopeCommandHandlers = array_values(
            array_filter($adminChatScopeCommandHandlers, static fn ($command): bool => true)
        );

        $localeCode = $bot->getEntity()->getLocaleCode();

        $defaultScope = new BotCommandScopeDefault();
        yield new TelegramBotMyCommands($defaultScopeCommandHandlers, $defaultScope, $localeCode);

        foreach ($bot->getEntity()->getAdminIds() as $adminId) {
            $adminChatScope = new BotCommandScopeChat(['chat_id' => $adminId]);

            yield new TelegramBotMyCommands($defaultScopeCommandHandlers, $adminChatScope, $localeCode);
        }
    }
}