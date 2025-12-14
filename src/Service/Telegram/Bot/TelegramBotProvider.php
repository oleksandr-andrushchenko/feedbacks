<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Entity\Telegram\TelegramBot;

class TelegramBotProvider
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function getCachedTelegramBotsByGroup(TelegramBotGroupName $group): array
    {
        static $cache = [];

        $key = implode('-', [
            $group->value,
        ]);

        if (!isset($cache[$key])) {
            $cache[$key] = $this->getTelegramBotsByGroup($group);
        }

        return $cache[$key];
    }

    public function getCachedTelegramBotsByGroupAndIds(TelegramBotGroupName $group, array $ids): array
    {
        return array_filter(
            $this->getCachedTelegramBotsByGroup($group),
            static fn (TelegramBot $bot): bool => in_array($bot->getId(), $ids, true)
        );
    }

    /**
     * @param TelegramBotGroupName $group
     * @return TelegramBot[]
     */
    public function getTelegramBotsByGroup(TelegramBotGroupName $group): array
    {
        return $this->telegramBotRepository->findPrimaryNonDeletedByGroup($group);
    }
}