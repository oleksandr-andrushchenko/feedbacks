<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;

class TelegramBotDescriptionsInfoProvider
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
    )
    {
    }

    public function getTelegramBotDescriptionsInfo(TelegramBot $botEntity): array
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);

        $data = [];

        return [
            'name' => $bot->getMyName($data)->getResult()->getName(),
            'short_description' => $bot->getMyShortDescription($data)->getResult()->getShortDescription(),
            'description' => $bot->getMyDescription($data)->getResult()->getDescription(),
        ];
    }
}