<?php
declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;

class TelegramChannelInfoProvider
{
    public function getTelegramChannelInfo(TelegramChannel $channel): array
    {
        return [
            'group' => $channel->getGroup()->name,
            'name' => $channel->getName(),
            'username' => $channel->getUsername(),
            'country' => $channel->getCountryCode(),
            'locale' => $channel->getLocaleCode(),
            'level_1_region_id' => $channel->getLevel1RegionId() ?? 'N/A',
            'chat_id' => $channel->getChatId() ?? 'N/A',
            'primary' => $channel->primary() ? 'Yes' : 'No',
            'created_at' => $channel->getCreatedAt()->format('Y-m-d H:i'),
            'updated_at' => $channel->getUpdatedAt() === null ? 'N/A' : $channel->getUpdatedAt()->format('Y-m-d H:i'),
            'deleted_at' => $channel->getDeletedAt() === null ? 'N/A' : $channel->getDeletedAt()->format('Y-m-d H:i'),
        ];
    }
}