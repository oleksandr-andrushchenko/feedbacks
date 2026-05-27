<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Enum\Telegram\TelegramBotGroupName;
use WeakMap;

class TelegramBotGroupRegistry
{
    public function __construct(
        private readonly TelegramBotGroupFactory $telegramBotGroupFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    public function getTelegramGroup(TelegramBotGroupName $name): TelegramBotGroupInterface
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        return $this->cache[$name] = $this->telegramBotGroupFactory->createTelegramBotGroup($name);
    }
}