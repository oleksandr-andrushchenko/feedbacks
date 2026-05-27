<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot as TelegramBotEntity;

class TelegramBotRegistry
{
    public function __construct(
        private readonly TelegramBotFactory $telegramBotFactory,
        /**
         * @var TelegramBot[]|null
         */
        private ?array $cache = null,
    )
    {
        $this->cache = [];
    }

    public function getTelegramBot(TelegramBotEntity $entity): TelegramBot
    {
        $username = $entity->getUsername();

        if (array_key_exists($username, $this->cache)) {
            return $this->cache[$username];
        }

        $this->cache[$username] = $this->telegramBotFactory->createTelegramBot($entity);

        return $this->cache[$username];
    }
}