<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScope;
use Longman\TelegramBot\Entities\Entity;

class TelegramBotMyCommands
{
    public function __construct(
        private readonly array $commands,
        private readonly BotCommandScope $scope,
        private readonly string $localeCode,
    )
    {
    }

    /**
     * @return TelegramBotCommandHandler[]
     */
    public function getCommandHandlers(): array
    {
        return $this->commands;
    }

    /**
     * @return BotCommandScope|Entity
     */
    public function getScope(): BotCommandScope
    {
        return $this->scope;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}